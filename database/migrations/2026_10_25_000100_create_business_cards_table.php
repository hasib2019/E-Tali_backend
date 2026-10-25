<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A business card the owner shares as a public link (/card/{token}). One
 * card per user + business: re-sharing updates it in place, so the link the
 * owner already sent keeps working and shows the latest details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The app's own business identifier (businesses live on the device).
            $table->string('business_key', 64);
            $table->string('token', 48)->unique();
            $table->string('template', 32);
            $table->json('data');
            $table->string('image_path')->nullable();
            $table->string('card_path')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'business_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_cards');
    }
};
