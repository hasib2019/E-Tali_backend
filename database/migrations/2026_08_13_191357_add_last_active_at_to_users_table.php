<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('last_backup_at');
            }
            if (! Schema::hasColumn('users', 'last_screen')) {
                $table->string('last_screen')->nullable()->after('last_active_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_active_at', 'last_screen']);
        });
    }
};
