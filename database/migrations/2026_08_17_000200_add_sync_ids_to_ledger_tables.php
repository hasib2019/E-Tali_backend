<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TABLES = [
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

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->string('sync_id', 64)->nullable()->after('id');
                $blueprint->unique('sync_id', "{$table}_sync_id_unique");
            });

            DB::table($table)
                ->whereNull('sync_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update([
                            'sync_id' => (string) Str::uuid(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropUnique("{$table}_sync_id_unique");
                $blueprint->dropColumn('sync_id');
            });
        }
    }
};
