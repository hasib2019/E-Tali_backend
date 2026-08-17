<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track record of a user claiming to have sent a bKash "Send Money" donation
 * for a tier. Honor-based (no payment gateway) — the owner verifies against
 * their bKash statement and flips `status` in the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tier_label');       // snapshot — survives tier rename/delete
            $table->decimal('amount', 10, 2);   // snapshot
            $table->string('bkash_number');     // snapshot — the number they were shown
            $table->string('trx_id')->nullable(); // optional bKash transaction id the user enters
            $table->string('status')->default('pending'); // pending | verified | rejected
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
