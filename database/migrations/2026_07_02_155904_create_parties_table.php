<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->enum('type', ['customer', 'supplier']);
            $table->string('address')->nullable();
            // Signed opening balance. Positive => party owes you (receivable).
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['business_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
