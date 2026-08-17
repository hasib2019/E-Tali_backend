<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Support us" donation tiers (Coffee / Burger / Pizza / … style), each mapped
 * to a taka amount and an Ionicons name. Fully owner-editable via Filament —
 * add/rename/reprice/reorder/disable any tier without a code change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->decimal('amount', 10, 2);
            $table->string('icon')->default('cafe-outline');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_tiers');
    }
};
