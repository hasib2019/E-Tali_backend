<?php

namespace App\Http\Controllers\Api;

use App\Models\DeviceMigration;
use App\Services\LedgerSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Diamond-tier live sync: the device pushes its incremental ledger changes
 * (read from its local `_sync_outbox`) here. The phone stays the source of
 * truth — this endpoint only mirrors it up. See LedgerSyncService for the
 * actual write logic and its ownership/collision guards.
 */
class LedgerSyncController extends ApiController
{
    public function push(Request $request, LedgerSyncService $service): JsonResponse
    {
        $user = $request->user();
        $features = $user->loadMissing('package')->package?->features ?? [];
        abort_unless(
            $user->hasActiveSubscription() && in_array('live_sync', $features, true),
            403,
            'Live sync requires an active Diamond plan.',
        );

        $data = $request->validate([
            'changes' => ['required', 'array', 'max:2000'],
            'changes.*.table' => ['required', 'string'],
            'changes.*.op' => ['required', 'string', 'in:upsert,delete'],
            'changes.*.row' => ['nullable', 'array'],
            'changes.*.id' => ['nullable', 'integer'],
        ]);

        $results = $service->applyChanges($user, $data['changes']);

        // updateOrCreate: a Diamond user who never had server data to migrate
        // (brand-new signup, 'not_applicable') has no DeviceMigration row yet.
        DeviceMigration::updateOrCreate(['user_id' => $user->id], ['last_synced_at' => now()]);

        return $this->ok(['results' => $results]);
    }
}
