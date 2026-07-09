<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Budgets become named envelopes (fuel, rent, bus…) with an optional planned
 * amount, instead of one budget row per month. Spending is tracked via cashbook
 * entries tagged with the budget name, so `period` is now nullable (unused for
 * named budgets). The existing unique(business_id, period) index is kept — MySQL
 * allows multiple NULL periods per business, so named budgets don't clash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->string('name')->nullable()->after('business_id');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->string('period', 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
