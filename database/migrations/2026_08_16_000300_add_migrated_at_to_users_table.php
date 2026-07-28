<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks a user as having completed the one-time server→device migration (the
 * server then wiped its own ledger copy — see MigrationController::confirm).
 * A user with this set who logs in on a fresh device has nothing to pull from
 * `/migration/status` (state stays 'done'); if they are on an active
 * backup-eligible plan, they must restore from their Drive-backed vault
 * backup instead. Never set for brand-new users (`not_applicable` state) —
 * only for accounts that actually went through the migration handoff.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('migrated_at')->nullable()->after('last_backup_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('migrated_at');
        });
    }
};
