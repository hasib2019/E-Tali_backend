<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Server mirror of the device's schema V7: the Business account's
 * income/expense fund (আয়-ব্যয়ের হিসাব), kept apart from the cashbook so
 * Diamond live sync can carry it. Money is decimal here, integer paisa on
 * device. `head` is the head's name, as on the device.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('head_entries', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 3);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('head')->nullable();
            $table->text('note')->nullable();
            $table->date('entry_date');
            $table->timestamps();
            $table->index(['business_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('head_entries');
    }
};
