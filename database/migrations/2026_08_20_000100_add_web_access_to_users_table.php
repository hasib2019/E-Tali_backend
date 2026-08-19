<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user gate for the web build. Defaults to false: only accounts the owner
 * explicitly whitelists via the Filament admin may log in and use the app on
 * web. Everyone else logging in on web is shown a "use the mobile app"
 * screen instead (see useMigrationGate.ts on the client).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('web_access')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('web_access');
        });
    }
};
