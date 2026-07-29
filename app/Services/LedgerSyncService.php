<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Applies Diamond device changes by stable sync_id, never by a device-local
 * integer primary key. Server ids remain ordinary global auto-increment ids;
 * relation sync ids are resolved to those server ids at the ingestion edge.
 */
class LedgerSyncService
{
    private const TABLE_ORDER = [
        'businesses',
        'batches',
        'parties',
        'products',
        'transactions',
        'vouchers',
        'voucher_items',
        'cashbook_entries',
        'landlord_properties',
        'landlord_units',
        'landlord_charges',
        'landlord_payments',
        'landlord_projects',
        'landlord_payment_allocations',
        'landlord_expenses',
        'landlord_recoveries',
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

    /** Columns shared with the device schema. */
    private const CLIENT_COLUMNS = [
        'businesses' => ['id', 'sync_id', 'user_id', 'name', 'type', 'category', 'phone', 'address', 'currency', 'meta', 'created_at', 'updated_at'],
        'batches' => ['id', 'sync_id', 'business_id', 'name', 'schedule', 'created_at', 'updated_at'],
        'parties' => ['id', 'sync_id', 'business_id', 'name', 'phone', 'type', 'address', 'opening_balance', 'monthly_fee', 'batch_id', 'roll', 'created_at', 'updated_at'],
        'products' => ['id', 'sync_id', 'business_id', 'name', 'unit', 'sale_price', 'purchase_price', 'stock', 'category', 'created_at', 'updated_at'],
        'transactions' => ['id', 'sync_id', 'business_id', 'party_id', 'type', 'amount', 'note', 'image_path', 'signature_path', 'txn_date', 'created_at', 'updated_at'],
        'vouchers' => ['id', 'sync_id', 'business_id', 'party_id', 'type', 'voucher_date', 'total_amount', 'paid_amount', 'due_amount', 'note', 'image_path', 'signature_path', 'created_at', 'updated_at'],
        'voucher_items' => ['id', 'sync_id', 'voucher_id', 'product_id', 'name', 'quantity', 'unit_price', 'line_total', 'created_at', 'updated_at'],
        'cashbook_entries' => ['id', 'sync_id', 'business_id', 'type', 'amount', 'category', 'note', 'image_path', 'signature_path', 'entry_date', 'created_at', 'updated_at'],
        'landlord_properties' => ['id', 'sync_id', 'business_id', 'name', 'address', 'kind', 'created_at', 'updated_at'],
        'landlord_units' => ['id', 'sync_id', 'business_id', 'property_id', 'name', 'floor', 'tenant_id', 'monthly_rent', 'is_active', 'created_at', 'updated_at'],
        'landlord_charges' => ['id', 'sync_id', 'business_id', 'property_id', 'unit_id', 'party_id', 'period', 'category', 'amount', 'due_date', 'note', 'created_at', 'updated_at'],
        'landlord_payments' => ['id', 'sync_id', 'business_id', 'party_id', 'unit_id', 'cashbook_entry_id', 'amount', 'paid_at', 'note', 'created_at', 'updated_at'],
        'landlord_projects' => ['id', 'sync_id', 'business_id', 'property_id', 'name', 'budget', 'started_at', 'status', 'created_at', 'updated_at'],
        'landlord_payment_allocations' => ['id', 'sync_id', 'payment_id', 'charge_id', 'amount', 'created_at'],
        'landlord_expenses' => ['id', 'sync_id', 'business_id', 'property_id', 'unit_id', 'project_id', 'cashbook_entry_id', 'category', 'amount', 'paid_at', 'payee', 'note', 'created_at', 'updated_at'],
        'landlord_recoveries' => ['id', 'sync_id', 'business_id', 'project_id', 'payment_id', 'amount', 'recovered_at', 'note', 'created_at', 'updated_at'],
        'cash_categories' => ['id', 'sync_id', 'business_id', 'type', 'name', 'icon', 'sort', 'created_at', 'updated_at'],
        'budgets' => ['id', 'sync_id', 'business_id', 'name', 'period', 'amount', 'created_at', 'updated_at'],
        'savings_goals' => ['id', 'sync_id', 'business_id', 'name', 'target_amount', 'saved_amount', 'target_date', 'is_done', 'created_at', 'updated_at'],
        'reminders' => ['id', 'sync_id', 'business_id', 'title', 'amount', 'due_date', 'is_done', 'note', 'created_at', 'updated_at'],
        'notes' => ['id', 'sync_id', 'business_id', 'title', 'body', 'created_at', 'updated_at'],
        'fee_payments' => ['id', 'sync_id', 'business_id', 'party_id', 'cashbook_entry_id', 'period', 'amount', 'paid_at', 'created_at', 'updated_at'],
        'attendances' => ['id', 'sync_id', 'business_id', 'party_id', 'date', 'status', 'created_at', 'updated_at'],
        'meals' => ['id', 'sync_id', 'business_id', 'party_id', 'period', 'count', 'created_at', 'updated_at'],
        'mess_entries' => ['id', 'sync_id', 'business_id', 'party_id', 'period', 'kind', 'amount', 'entry_date', 'note', 'created_at', 'updated_at'],
    ];

    /**
     * Device relation column => server target table. Every payload carries the
     * matching synthetic key (for example business_sync_id); numeric device ids
     * are never trusted or used as server foreign keys.
     */
    private const RELATIONS = [
        'batches' => ['business_id' => ['businesses', false]],
        'parties' => ['business_id' => ['businesses', false], 'batch_id' => ['batches', true]],
        'products' => ['business_id' => ['businesses', false]],
        'transactions' => ['business_id' => ['businesses', false], 'party_id' => ['parties', false]],
        'vouchers' => ['business_id' => ['businesses', false], 'party_id' => ['parties', false]],
        'voucher_items' => ['voucher_id' => ['vouchers', false], 'product_id' => ['products', true]],
        'cashbook_entries' => ['business_id' => ['businesses', false]],
        'landlord_properties' => ['business_id' => ['businesses', false]],
        'landlord_units' => [
            'business_id' => ['businesses', false],
            'property_id' => ['landlord_properties', false],
            'tenant_id' => ['parties', true],
        ],
        'landlord_charges' => [
            'business_id' => ['businesses', false],
            'property_id' => ['landlord_properties', false],
            'unit_id' => ['landlord_units', false],
            'party_id' => ['parties', false],
        ],
        'landlord_payments' => [
            'business_id' => ['businesses', false],
            'party_id' => ['parties', false],
            'unit_id' => ['landlord_units', true],
            'cashbook_entry_id' => ['cashbook_entries', true],
        ],
        'landlord_projects' => [
            'business_id' => ['businesses', false],
            'property_id' => ['landlord_properties', false],
        ],
        'landlord_payment_allocations' => [
            'payment_id' => ['landlord_payments', false],
            'charge_id' => ['landlord_charges', false],
        ],
        'landlord_expenses' => [
            'business_id' => ['businesses', false],
            'property_id' => ['landlord_properties', true],
            'unit_id' => ['landlord_units', true],
            'project_id' => ['landlord_projects', true],
            'cashbook_entry_id' => ['cashbook_entries', true],
        ],
        'landlord_recoveries' => [
            'business_id' => ['businesses', false],
            'project_id' => ['landlord_projects', false],
            'payment_id' => ['landlord_payments', true],
        ],
        'cash_categories' => ['business_id' => ['businesses', false]],
        'budgets' => ['business_id' => ['businesses', false]],
        'savings_goals' => ['business_id' => ['businesses', false]],
        'reminders' => ['business_id' => ['businesses', false]],
        'notes' => ['business_id' => ['businesses', false]],
        'fee_payments' => [
            'business_id' => ['businesses', false],
            'party_id' => ['parties', false],
            'cashbook_entry_id' => ['cashbook_entries', true],
        ],
        'attendances' => ['business_id' => ['businesses', false], 'party_id' => ['parties', false]],
        'meals' => ['business_id' => ['businesses', false], 'party_id' => ['parties', false]],
        'mess_entries' => ['business_id' => ['businesses', false], 'party_id' => ['parties', true]],
    ];

    private const USER_ID_TABLES = ['businesses', 'transactions', 'vouchers', 'cashbook_entries'];

    private const MEDIA_TABLES = [
        'transactions' => 'transaction',
        'vouchers' => 'vouchers',
        'cashbook_entries' => 'cashbook',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $changes
     * @return array<int, array<string, mixed>>
     */
    public function applyChanges(User $user, array $changes): array
    {
        $ordered = array_values($changes);
        usort($ordered, fn (array $a, array $b) => $this->tableRank($a['table'] ?? null) <=> $this->tableRank($b['table'] ?? null));

        return array_map(function (array $change) use ($user): array {
            try {
                return DB::transaction(fn () => $this->applyOne($user, $change));
            } catch (Throwable $error) {
                report($error);

                return $this->result($change, false, 'write_failed');
            }
        }, $ordered);
    }

    /** Full cloud snapshot used only to hydrate an empty scoped device DB. */
    public function snapshot(User $user): array
    {
        $businessIds = DB::table('businesses')->where('user_id', $user->id)->pluck('id');
        $tables = [];

        foreach (self::TABLE_ORDER as $table) {
            $columns = array_values(array_intersect(
                self::CLIENT_COLUMNS[$table],
                Schema::getColumnListing($table),
            ));

            $query = DB::table($table)->orderBy("{$table}.id");
            if ($table === 'businesses') {
                $query->where('user_id', $user->id);
            } elseif ($table === 'voucher_items') {
                $query->whereIn('voucher_id', DB::table('vouchers')->whereIn('business_id', $businessIds)->select('id'));
            } elseif ($table === 'landlord_payment_allocations') {
                $query->whereIn('payment_id', DB::table('landlord_payments')->whereIn('business_id', $businessIds)->select('id'));
            } else {
                $query->whereIn('business_id', $businessIds);
            }

            $rows = $query->get($columns)->map(function ($row) use ($table): array {
                $data = (array) $row;
                if (isset(self::MEDIA_TABLES[$table])) {
                    foreach (['image_path', 'signature_path'] as $column) {
                        $data[$column] = MediaStorage::dataUri($data[$column] ?? null);
                    }
                }

                return $data;
            })->all();

            $tables[$table] = $rows;
        }

        return [
            'version' => 2,
            'user_id' => $user->id,
            'exported_at' => now()->toIso8601String(),
            'counts' => array_map('count', $tables),
            'tables' => $tables,
        ];
    }

    private function tableRank(mixed $table): int
    {
        $index = is_string($table) ? array_search($table, self::TABLE_ORDER, true) : false;

        return $index === false ? PHP_INT_MAX : $index;
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
        $syncId = is_array($row) ? ($row['sync_id'] ?? null) : null;
        if (! is_array($row) || ! is_string($syncId) || $syncId === '' || strlen($syncId) > 64) {
            return $this->result($change, false, 'malformed_row');
        }

        $existing = DB::table($table)->where('sync_id', $syncId)->first();
        if ($existing && $this->ownerUserId($table, (int) $existing->id) !== $user->id) {
            return $this->result($change, false, 'sync_id_not_owned');
        }

        $resolved = $this->resolveRelations($user, $table, $row);
        if (is_string($resolved)) {
            return $this->result($change, false, $resolved);
        }
        $row = $resolved;

        $allowed = array_flip(array_diff(self::CLIENT_COLUMNS[$table], ['id', 'user_id']));
        $row = array_intersect_key($row, $allowed);
        $row['sync_id'] = $syncId;

        if (in_array($table, self::USER_ID_TABLES, true)) {
            $row['user_id'] = $user->id;
        }

        $row = $this->normalizeTimestamps($row);

        if ($existing) {
            $serverId = (int) $existing->id;
            $row = $this->materializeMedia($table, $serverId, $row, (array) $existing);
            DB::table($table)->where('id', $serverId)->update($row);
        } else {
            // Media paths depend on the auto-generated server id. Insert the
            // structured row first, then materialize media into that row.
            $media = array_filter(
                array_intersect_key($row, array_flip(['image_path', 'signature_path'])),
                fn ($value) => $value !== null && $value !== '',
            );
            $row = array_diff_key($row, $media);
            $serverId = (int) DB::table($table)->insertGetId($row);
            if ($media) {
                $stored = $this->materializeMedia($table, $serverId, $media);
                if ($stored) {
                    DB::table($table)->where('id', $serverId)->update($stored);
                }
            }
        }

        return $this->result($change, true, null, $serverId);
    }

    private function applyDelete(User $user, string $table, array $change): array
    {
        $syncId = $change['sync_id'] ?? null;
        if (! is_string($syncId) || $syncId === '') {
            return $this->result($change, false, 'malformed_delete');
        }

        $existing = DB::table($table)->where('sync_id', $syncId)->first();
        if (! $existing) {
            return $this->result($change, true, null);
        }
        if ($this->ownerUserId($table, (int) $existing->id) !== $user->id) {
            return $this->result($change, false, 'not_owned');
        }

        if ($table === 'businesses') {
            $this->deleteBusinessMedia((int) $existing->id);
        } elseif (isset(self::MEDIA_TABLES[$table])) {
            MediaStorage::delete($existing->image_path ?? null, $existing->signature_path ?? null);
        }

        DB::table($table)->where('id', $existing->id)->delete();

        return $this->result($change, true, null, (int) $existing->id);
    }

    /** @return array<string, mixed>|string */
    private function resolveRelations(User $user, string $table, array $row): array|string
    {
        foreach (self::RELATIONS[$table] ?? [] as $column => [$targetTable, $nullable]) {
            $syncKey = substr($column, 0, -3).'_sync_id';
            $relationSyncId = $row[$syncKey] ?? null;

            if (($relationSyncId === null || $relationSyncId === '') && $nullable && ($row[$column] ?? null) === null) {
                $row[$column] = null;

                continue;
            }
            if (! is_string($relationSyncId) || $relationSyncId === '') {
                return "missing_{$syncKey}";
            }

            $target = DB::table($targetTable)->where('sync_id', $relationSyncId)->first();
            if (! $target || $this->ownerUserId($targetTable, (int) $target->id) !== $user->id) {
                return "{$syncKey}_not_owned";
            }

            $row[$column] = (int) $target->id;
        }

        return $row;
    }

    private function ownerUserId(string $table, int $id): ?int
    {
        if ($table === 'businesses') {
            $userId = DB::table('businesses')->where('id', $id)->value('user_id');

            return $userId === null ? null : (int) $userId;
        }

        if ($table === 'voucher_items') {
            $voucherId = DB::table('voucher_items')->where('id', $id)->value('voucher_id');
            $businessId = $voucherId === null
                ? null
                : DB::table('vouchers')->where('id', $voucherId)->value('business_id');
        } elseif ($table === 'landlord_payment_allocations') {
            $paymentId = DB::table('landlord_payment_allocations')->where('id', $id)->value('payment_id');
            $businessId = $paymentId === null
                ? null
                : DB::table('landlord_payments')->where('id', $paymentId)->value('business_id');
        } else {
            $businessId = DB::table($table)->where('id', $id)->value('business_id');
        }

        if ($businessId === null) {
            return null;
        }

        $userId = DB::table('businesses')->where('id', $businessId)->value('user_id');

        return $userId === null ? null : (int) $userId;
    }

    private function normalizeTimestamps(array $row): array
    {
        foreach (['created_at', 'updated_at'] as $column) {
            if (! empty($row[$column]) && is_string($row[$column])) {
                $row[$column] = Carbon::parse($row[$column])->format('Y-m-d H:i:s');
            }
        }

        return $row;
    }

    private function materializeMedia(string $table, int $serverId, array $row, array $existing = []): array
    {
        $folder = self::MEDIA_TABLES[$table] ?? null;
        if ($folder === null) {
            return $row;
        }

        $directory = $table === 'vouchers' ? "vouchers/{$serverId}" : "{$folder}/{$serverId}";
        foreach (['image_path' => 'image', 'signature_path' => 'signature'] as $column => $name) {
            $value = $row[$column] ?? null;
            if (! is_string($value) || ! str_starts_with($value, 'data:image/')) {
                unset($row[$column]);

                continue;
            }

            $stored = MediaStorage::storeBase64($value, $directory, $name);
            if ($stored === null) {
                throw new \InvalidArgumentException("Invalid {$column} payload.");
            }
            if (($existing[$column] ?? null) && $existing[$column] !== $stored) {
                MediaStorage::delete($existing[$column]);
            }
            $row[$column] = $stored;
        }

        return $row;
    }

    private function deleteBusinessMedia(int $businessId): void
    {
        foreach (array_keys(self::MEDIA_TABLES) as $table) {
            DB::table($table)
                ->where('business_id', $businessId)
                ->select(['image_path', 'signature_path'])
                ->orderBy('id')
                ->each(fn ($row) => MediaStorage::delete($row->image_path, $row->signature_path));
        }
    }

    private function result(array $change, bool $accepted, ?string $reason, ?int $serverId = null): array
    {
        return [
            'table' => $change['table'] ?? null,
            'op' => $change['op'] ?? null,
            'id' => ($change['op'] ?? null) === 'delete'
                ? ($change['id'] ?? null)
                : ($change['row']['id'] ?? null),
            'sync_id' => ($change['op'] ?? null) === 'delete'
                ? ($change['sync_id'] ?? null)
                : ($change['row']['sync_id'] ?? null),
            'server_id' => $serverId,
            'accepted' => $accepted,
            'reason' => $reason,
        ];
    }
}
