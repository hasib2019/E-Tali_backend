<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mess / hostel manager tables:
 *  - meals: how many meals each member ate in a month.
 *  - mess_entries: money into the fund (deposit) and grocery spend (bazar).
 * Meal rate = total bazar ÷ total meals; each member's bill = deposits+own-bazar
 * minus (their meals × rate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);         // YYYY-MM
            $table->decimal('count', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['party_id', 'period']);
            $table->index(['business_id', 'period']);
        });

        Schema::create('mess_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 7);         // YYYY-MM
            $table->string('kind');              // deposit | bazar
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mess_entries');
        Schema::dropIfExists('meals');
    }
};
