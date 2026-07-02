<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // sale = বিক্রি (to a customer), purchase = ক্রয় (from a supplier)
            $table->enum('type', ['sale', 'purchase']);
            $table->date('voucher_date');
            $table->decimal('total_amount', 15, 2)->default(0);   // Σ line totals
            $table->decimal('paid_amount', 15, 2)->default(0);    // cash settled now
            $table->decimal('due_amount', 15, 2)->default(0);     // total - paid
            $table->text('note')->nullable();
            $table->string('image_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'voucher_date']);
            $table->index(['party_id', 'voucher_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
