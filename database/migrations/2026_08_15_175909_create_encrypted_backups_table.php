<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-hosting copy of a user's encrypted device backup + its track record.
 * The stored blob is opaque ciphertext (AES-256-CBC+HMAC) — the server can only
 * hold/serve it; decryption needs the owner master key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encrypted_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('backup_uuid')->unique();     // idempotency key from the device
            $table->string('file_name');
            $table->string('storage_path');            // private disk path to the .enc blob
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum')->nullable();    // sha256 of the ciphertext
            $table->string('drive_file_id')->nullable(); // matching copy on the user's Drive
            $table->string('source')->default('manual'); // manual | auto
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encrypted_backups');
    }
};
