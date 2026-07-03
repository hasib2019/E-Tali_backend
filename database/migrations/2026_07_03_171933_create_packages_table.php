<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days');            // subscription length granted, in days
            $table->string('description')->nullable();
            $table->unsignedInteger('max_businesses')->nullable(); // null = unlimited
            $table->unsignedInteger('max_parties')->nullable();    // null = unlimited
            $table->boolean('is_active')->default(true);         // assignable / shown to users
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
