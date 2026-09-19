<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What users tell us about the app itself — suggestions, what they want
 * changed, bugs. Server-side only: this is for the team to read in the admin
 * panel, not ledger data that belongs on the device.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->unsignedTinyInteger('rating')->nullable(); // 1–5, optional
            $table->string('app_version')->nullable();
            $table->string('platform')->nullable(); // android | ios | web
            $table->string('status')->default('new'); // new | read | resolved
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_feedback');
    }
};
