<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Diamond-tier live sync: applies a device's incremental ledger changes
 * (upserts/deletes, id-preserving) to the same MySQL tables MigrationService
 * reads from. Deliberately NOT built on BackupService — that pipeline mints
 * fresh ids on every write and is architecturally incompatible with an
 * id-preserving upsert.
 *
 * MySQL's ledger tables share ONE global auto-increment counter across every
 * account (confirmed still advancing today via MigrationController::
 * restoreArchive's plain Eloquent creates), so a device's id — unique only at
 * its own migration time — can coincide with an id MySQL has since reused for
 * a DIFFERENT account. Every write here is therefore guarded: before
 * inserting or updating by id, verify who (if anyone) already owns that id,
 * and refuse to touch a row owned by someone else rather than silently
 * overwrite it.
 *
 * Processes entries individually with a per-entry accept/reject result —
 * never one all-or-nothing transaction, so one bad/stale/colliding entry
 * cannot block every other legitimate row in the same push.
 */
class LedgerSyncService
{
    /** Ledger tables that exist on the server — mirrors the device's
     * SERVER_SYNCED_TABLES (schema.ts), parents before children so upserts
     * resolve FKs within a single batch. `landlord_*` has no MySQL
     * counterpart yet and is never sent by the device for this table list. */
    private const TABLE_ORDER = [
        'businesses',
        'batches',
        'parties',
        'products',
        'transactions',
        'vouchers',
        'voucher_items',
        'cashbook_entries',
        'cash_categories',
        'budgets',
        'savings_goals',
        'reminders',
        'notes',
        'fee_payments',
        'attendances',
        'meals',
        'mess_entries',
    ];

    /** `vouchers.image_path`/`signature_path` hold a real storage-disk file
     * path (VoucherController::storeBase64's contract) — unlike
     * transactions/cashbook_entries, which store the raw base64 verbatim. */
    private const VOUCHER_MEDIA_COLUMNS = ['image_path', 'signature_path'];

    /**
     * @param  array<int, array{table?: string, op?: string, row?: array, id?: int}>  $changes
     * @return array<int, array{table: ?string, op: ?string, id: ?int, accepted: bool, reason: ?string}>
     */
    public function applyChanges(User $user, array $changes): array
    {
        $ordered = array_values($changes);
        usort($ordered, fn (array $a, array $b) => $this->tableRank($a['table'] ?? null) <=> $this->tableRank($b['table'] ?? null));

        return array_map(fn (array $change) => $this->applyOne($user, $change), $ordered);
    }

    private function tableRank(?string $table): int
    {
        $i = array_search($table, self::TABLE_ORDER, true);

        return $i === false ? PHP_INT_MAX : $i;
    }

    private function applyOne(User $user, array $change): array
    {
        $table = $change['table'] ?? null;
        $op = $change['op'] ?? null;

        if (! is_string($table) || ! in_array($table, self::TABLE_ORDER, true)) {
            return $this->result($change, false, 'unknown_table');
        }

        return match ($op) {
            'upsert' => $this->applyUpsert($user, $table, $change),
            'delete' => $this->applyDelete($user, $table, $change),
            default => $this->result($change, false, 'unknown_op'),
        };
    }

    private function applyUpsert(User $user, string $table, array $change): array
    {
        $row = $change['row'] ?? null;
        if (! is_array($row) || ! isset($row['id'])) {
            return $this->result($change, false, 'malformed_row');
        }
        $id = (int) $row['id'];

        $existingOwnerId = $this->ownerUserId($table, $id);
        if ($existingOwnerId !== null && $existingOwnerId !== $user->id) {
            return $this->result($change, false, 'id_collision');
        }

        if ($existingOwnerId === null && $table !== 'businesses') {
            // Brand-new row: verify the parent it claims is actually this
            // user's before it's allowed to land at all.
            $businessId = $this->claimedBusinessId($table, $row);
            if ($businessId === null || ! Business::where('id', $businessId)->where('user_id', $user->id)->exists()) {
                return $this->result($change, false, 'business_not_owned');
            }
        }

        unset($row['id']);
        if ($table === 'businesses') {
            // Never trust the client's claimed owner, new row or existing.
            $row['user_id'] = $user->id;
        }
        if ($table === 'vouchers') {
            $row = $this->materializeVoucherMedia($id, $row);
        }

        DB::table($table)->updateOrInsert(['id' => $id], $row);

        return $this->result($change, true, null);
    }

    private function applyDelete(User $user, string $table, array $change): array
    {
        $id = (int) ($change['id'] ?? 0);
        if (! $id) {
            return $this->result($change, false, 'malformed_delete');
        }

        $existingOwnerId = $this->ownerUserId($table, $id);
        if ($existingOwnerId === null) {
            return $this->result($change, true, null); // already gone — nothing to do
        }
        if ($existingOwnerId !== $user->id) {
            return $this->result($change, false, 'not_owned');
        }

        DB::table($table)->where('id', $id)->delete();

        return $this->result($change, true, null);
    }

    /** Which user currently owns row $id in $table, if it exists at all. */
    private function ownerUserId(string $table, int $id): ?int
    {
        if ($table === 'businesses') {
            $userId = DB::table('businesses')->where('id', $id)->value('user_id');

            return $userId !== null ? (int) $userId : null;
        }

        $businessId = $this->currentBusinessId($table, $id);
        if ($businessId === null) {
            return null;
        }
        $userId = DB::table('businesses')->where('id', $businessId)->value('user_id');

        return $userId !== null ? (int) $userId : null;
    }

    /** The business_id an EXISTING row in $table currently has. */
    private function currentBusinessId(string $table, int $id): ?int
    {
        if ($table === 'voucher_items') {
            $voucherId = DB::table('voucher_items')->where('id', $id)->value('voucher_id');

            return $voucherId !== null ? $this->businessIdOfVoucher((int) $voucherId) : null;
        }

        $businessId = DB::table($table)->where('id', $id)->value('business_id');

        return $businessId !== null ? (int) $businessId : null;
    }

    /** The business_id a NEW (not-yet-existing) row's payload claims. */
    private function claimedBusinessId(string $table, array $row): ?int
    {
        if ($table === 'voucher_items') {
            $voucherId = $row['voucher_id'] ?? null;

            return $voucherId !== null ? $this->businessIdOfVoucher((int) $voucherId) : null;
        }

        return isset($row['business_id']) ? (int) $row['business_id'] : null;
    }

    private function businessIdOfVoucher(int $voucherId): ?int
    {
        $businessId = DB::table('vouchers')->where('id', $voucherId)->value('business_id');

        return $businessId !== null ? (int) $businessId : null;
    }

    /** Decode+store any base64 voucher media as a real file, matching
     * VoucherController's existing on-disk contract, instead of writing the
     * raw data URI into a path column. */
    private function materializeVoucherMedia(int $voucherId, array $row): array
    {
        foreach (self::VOUCHER_MEDIA_COLUMNS as $col) {
            $value = $row[$col] ?? null;
            if (is_string($value) && str_starts_with($value, 'data:')) {
                $name = $col === 'image_path' ? 'image' : 'signature';
                $row[$col] = MediaStorage::storeBase64($value, "vouchers/{$voucherId}", $name);
            }
        }

        return $row;
    }

    private function result(array $change, bool $accepted, ?string $reason): array
    {
        return [
            'table' => $change['table'] ?? null,
            'op' => $change['op'] ?? null,
            'id' => $change['op'] === 'delete' ? ($change['id'] ?? null) : ($change['row']['id'] ?? null),
            'accepted' => $accepted,
            'reason' => $reason,
        ];
    }
}
