<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Observability only for Diamond-tier live sync — read-only telemetry, not a
 * cursor the sync logic depends on (the device is the only source of truth
 * for what's dirty; this just answers "when did we last hear from them").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_migrations', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('device_migrations', function (Blueprint $table) {
            $table->dropColumn('last_synced_at');
        });
    }
};
