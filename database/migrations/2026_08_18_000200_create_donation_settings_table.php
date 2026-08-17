<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row settings for the "Support us" screen: which bKash number to show
 * for every tier, plus the intro title/message copy. Owner-editable via a
 * Filament settings page (App\Filament\Pages\ManageDonationSettings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bkash_number')->nullable();
            $table->string('intro_title')->nullable();
            $table->text('intro_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_settings');
    }
};
