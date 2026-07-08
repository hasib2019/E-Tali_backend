<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher / tutor building blocks: class batches, per-student monthly fee,
 * fee collection records, and attendance. All additive — existing parties
 * keep working; the new party columns are nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('schedule')->nullable();
            $table->timestamps();
            $table->index('business_id');
        });

        // A student is just a party; give parties the teacher-only fields.
        Schema::table('parties', function (Blueprint $table) {
            $table->decimal('monthly_fee', 12, 2)->nullable()->after('opening_balance');
            $table->foreignId('batch_id')->nullable()->after('monthly_fee')->constrained()->nullOnDelete();
            $table->string('roll')->nullable()->after('batch_id');
        });

        // One fee collection record per student per month. Optionally linked to
        // the cashbook entry it created (so income shows up in one place).
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashbook_entry_id')->nullable()->constrained('cashbook_entries')->nullOnDelete();
            $table->string('period', 7);        // YYYY-MM
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->timestamps();
            $table->unique(['party_id', 'period']);
            $table->index(['business_id', 'period']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status');           // present | absent | late
            $table->timestamps();
            $table->unique(['party_id', 'date']);
            $table->index(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('fee_payments');
        Schema::table('parties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropColumn(['monthly_fee', 'roll']);
        });
        Schema::dropIfExists('batches');
    }
};
