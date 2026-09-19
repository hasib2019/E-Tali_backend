<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short-lived codes for "forgot password", delivered over email or SMS.
 * Laravel's own password_reset_tokens table is keyed by email and built for
 * the browser link flow; the app needs a 6-digit code that can arrive on
 * either channel, with its own expiry and wrong-attempt cap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel'); // email | phone
            $table->string('destination'); // the email/phone it was sent to
            $table->string('code'); // hashed, never stored in plaintext
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
