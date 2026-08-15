<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesMedia;
use App\Models\DeviceMigration;
use App\Services\MigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * One-time server→device migration for existing users. Two-phase and safe:
 * the server exports a verified snapshot but keeps all live ledger data until
 * the device confirms a successful import (`confirm`). Nothing is deleted here.
 */
class MigrationController extends ApiController
{
    use HandlesMedia;

    private const MEDIA_TABLES = ['transactions', 'vouchers', 'cashbook_entries'];

    /**
     * Where this user stands:
     *  - done           → already migrated (device should read SQLite)
     *  - not_applicable → no server ledger (new user; nothing to migrate)
     *  - required       → has server data, not yet migrated
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $migration = DeviceMigration::where('user_id', $user->id)->first();

        if ($migration && $migration->status === 'confirmed') {
            $state = 'done';
        } elseif (! $user->businesses()->exists()) {
            $state = 'not_applicable';
        } else {
            $state = 'required';
        }

        return $this->ok([
            'state' => $state,
            'migration_id' => $migration?->id,
            'confirmed_at' => $migration?->confirmed_at,
        ]);
    }

    /** Phase 1: freeze + return a verified id-carrying snapshot. Server data stays intact. */
    public function export(Request $request, MigrationService $service): JsonResponse
    {
        $user = $request->user();
        $snapshot = $service->snapshot($user);
        $checksum = hash('sha256', json_encode($snapshot));

        $migration = DeviceMigration::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'exported',
                'checksum' => $checksum,
                'counts' => $snapshot['counts'],
                'exported_at' => now(),
            ],
        );

        return $this->ok([
            'migration_id' => $migration->id,
            'checksum' => $checksum,
            'counts' => $snapshot['counts'],
            'snapshot' => $snapshot,
        ]);
    }

    /** Phase 2: the device proved a verified import — mark migrated (still no delete). */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'migration_id' => ['required', 'integer'],
            'checksum' => ['required', 'string'],
            'device_id' => ['nullable', 'string', 'max:120'],
        ]);

        $migration = DeviceMigration::where('user_id', $request->user()->id)
            ->where('id', $data['migration_id'])
            ->firstOrFail();

        abort_unless($migration->checksum === $data['checksum'], 422, 'Snapshot checksum mismatch — please retry.');

        $migration->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'device_id' => $data['device_id'] ?? null,
        ]);

        return $this->ok(['state' => 'done']);
    }

    /**
     * Media-sync (separate track): which of the user's records carry a
     * photo/signature. Returns just the ids (no bytes) so the device can then
     * pull them in small batches. {transactions: [id…], vouchers: […], …}.
     */
    public function mediaManifest(Request $request): JsonResponse
    {
        $businessIds = $request->user()->businesses()->pluck('id');

        $manifest = [];
        foreach (self::MEDIA_TABLES as $table) {
            $manifest[$table] = DB::table($table)
                ->whereIn('business_id', $businessIds)
                ->where(fn ($q) => $q->whereNotNull('image_path')->orWhereNotNull('signature_path'))
                ->orderBy('id')
                ->pluck('id');
        }

        return $this->ok($manifest);
    }

    /** Return the media (as data URIs) for a small batch of record ids. */
    public function mediaBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'table' => ['required', 'in:transactions,vouchers,cashbook_entries'],
            'ids' => ['required', 'array', 'max:20'],
            'ids.*' => ['integer'],
        ]);

        $businessIds = $request->user()->businesses()->pluck('id');
        $rows = DB::table($data['table'])
            ->whereIn('business_id', $businessIds)
            ->whereIn('id', $data['ids'])
            ->get(['id', 'image_path', 'signature_path']);

        $items = $rows->map(fn ($r) => [
            'id' => $r->id,
            'image' => $this->mediaDataUri($r->image_path),
            'signature' => $this->mediaDataUri($r->signature_path),
        ]);

        return $this->ok($items);
    }
}
