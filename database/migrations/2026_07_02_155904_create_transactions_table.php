<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // debit  = you gave  (দিলাম) -> increases receivable (party owes you more)
            // credit = you got   (পেলাম) -> decreases receivable (party owes you less)
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->date('txn_date');
            $table->timestamps();

            $table->index(['party_id', 'txn_date']);
            $table->index(['business_id', 'txn_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
