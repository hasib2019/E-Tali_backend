<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user record of the one-time server→device (SQLite) data migration.
 * The server NEVER deletes ledger data until the device confirms a verified
 * import; this row tracks that two-phase handshake.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | exported | confirmed
            $table->string('checksum')->nullable();        // sha256 of the exported snapshot
            $table->json('counts')->nullable();            // per-table row counts (integrity gate)
            $table->string('device_id')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_migrations');
    }
};
