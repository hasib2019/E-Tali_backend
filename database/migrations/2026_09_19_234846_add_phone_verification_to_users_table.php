<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which channel the user chose at registration ('email' | 'phone').
            // Existing rows default to 'email' — the only flow that existed before.
            if (! Schema::hasColumn('users', 'verification_method')) {
                $table->string('verification_method')->default('email')->after('phone');
            }

            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            }
        });

        // Enforce one phone per account, same as email. Safe to add: all current
        // rows have a null phone, and MySQL allows multiple NULLs in a unique index.
        if (! Schema::hasIndex('users', ['phone'], 'unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', ['phone'], 'unique')) {
                $table->dropUnique(['phone']);
            }

            $table->dropColumn(array_values(array_filter([
                Schema::hasColumn('users', 'verification_method') ? 'verification_method' : null,
                Schema::hasColumn('users', 'phone_verified_at') ? 'phone_verified_at' : null,
            ])));
        });
    }
};
