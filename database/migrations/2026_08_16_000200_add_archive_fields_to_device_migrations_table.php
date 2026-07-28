<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks the post-migration server-side archive: once a device confirms it has
 * safely imported the ledger, the server snapshots it to disk (as a safety net
 * for a later premium restore) and wipes its own copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_migrations', function (Blueprint $table) {
            $table->string('archive_path')->nullable()->after('device_id');
            $table->timestamp('archived_at')->nullable()->after('archive_path');
        });
    }

    public function down(): void
    {
        Schema::table('device_migrations', function (Blueprint $table) {
            $table->dropColumn(['archive_path', 'archived_at']);
        });
    }
};
