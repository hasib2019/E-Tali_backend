<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesMedia;
use App\Models\DeviceMigration;
use App\Models\User;
use App\Services\BackupService;
use App\Services\MigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-time server→device migration for existing users. Two-phase and safe:
 * the server exports a verified snapshot but keeps all live ledger data until
 * the device confirms a successful import (`confirm`). Once confirmed, the
 * server archives a JSON snapshot to disk and wipes its own ledger copy — the
 * device is now the source of truth. The archive is a safety net: a later
 * Yearly upgrade can pull it back via `restoreArchive`, which re-arms this
 * same migration record so a device then re-pulls it through `status`/
 * `export`/`confirm` exactly as on first migration.
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

    /**
     * Phase 2: the device proved a verified import — mark migrated, then archive
     * the ledger to disk and wipe the server's copy (the device now owns it).
     */
    public function confirm(Request $request, BackupService $backup): JsonResponse
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

        // Idempotency guard: confirm() can only meaningfully archive+wipe once —
        // a retried/duplicate confirm call (same migration_id+checksum) must not
        // re-run this against an already-wiped (now empty) ledger.
        if (! $migration->archived_at) {
            $this->archiveAndWipe($request->user(), $migration, $backup);
        }

        return $this->ok(['state' => 'done']);
    }

    /**
     * Snapshot the user's full server ledger to disk (reusing the same
     * export/import pair the Drive backup feature already relies on), then
     * delete their businesses (FK cascade clears every child row). The device
     * already holds a verified copy at this point, so this is not data loss —
     * it's freeing the server of ledger data it no longer needs to serve,
     * while keeping one JSON copy in reserve.
     */
    private function archiveAndWipe(User $user, DeviceMigration $migration, BackupService $backup): void
    {
        $snapshot = $backup->export($user);
        $path = "migration-archives/{$user->id}/{$migration->id}.json";
        Storage::disk('local')->put($path, json_encode($snapshot));

        DB::transaction(function () use ($user, $migration, $path) {
            $user->businesses()->delete();
            $user->update(['migrated_at' => now()]);
            $migration->update(['archive_path' => $path, 'archived_at' => now()]);
        });
    }

    /**
     * Let a user who has since upgraded to a backup-eligible plan (Yearly) pull
     * their pre-migration snapshot back into the server ledger, then RE-ARM the
     * migration record so a device picks it up through the normal flow.
     *
     * `status()` only ever looks at `device_migrations.status` — it never
     * inspects `businesses` directly for a 'confirmed' row. So simply importing
     * data back into `businesses` here would be invisible to every device:
     * `status()` would still report 'done' and nothing would be pulled. Clearing
     * this row back to its pre-migration shape (status != 'confirmed', but the
     * row still exists) makes `status()` fall through to 'required', since
     * `businesses()->exists()` is now true. The next device that logs in then
     * runs the ordinary export→confirm dance, and `confirm()` re-archives and
     * re-wipes exactly as on first migration — `device_migrations` remains the
     * single source of truth throughout, with no separate "restored" flag.
     */
    public function restoreArchive(Request $request, BackupService $backup): JsonResponse
    {
        $user = $request->user();
        $features = $user->loadMissing('package')->package?->features ?? [];
        abort_unless(
            $user->hasActiveSubscription() && in_array('backup', $features, true),
            403,
            'Restoring your pre-migration backup requires an active Yearly plan.',
        );

        $migration = DeviceMigration::where('user_id', $user->id)->whereNotNull('archive_path')->first();
        abort_unless($migration, 404, 'No archived pre-migration backup found for this account.');

        $json = Storage::disk('local')->get($migration->archive_path);
        abort_if($json === null, 404, 'Archived backup file is missing.');

        $counts = $backup->import($user, json_decode($json, true), 'replace');

        Storage::disk('local')->delete($migration->archive_path);
        $migration->update([
            'status' => 'pending',
            'checksum' => null,
            'exported_at' => null,
            'confirmed_at' => null,
            'device_id' => null,
            'archive_path' => null,
            'archived_at' => null,
        ]);

        return $this->ok(['counts' => $counts], 'Restored — a device can now pull this down via the normal migration flow.');
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
