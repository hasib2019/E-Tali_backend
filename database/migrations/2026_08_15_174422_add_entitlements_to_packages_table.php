<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-configurable entitlements per package: which khata categories are
 * allowed and which app features are unlocked. `max_businesses`/`max_parties`
 * already exist. null allowed_categories = all categories; features is a list
 * of enabled feature keys the app checks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (! Schema::hasColumn('packages', 'allowed_categories')) {
                $table->json('allowed_categories')->nullable()->after('max_parties');
            }
            if (! Schema::hasColumn('packages', 'features')) {
                $table->json('features')->nullable()->after('allowed_categories');
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['allowed_categories', 'features']);
        });
    }
};
