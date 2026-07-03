<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();   // encrypted at rest (model cast)
            $table->text('refresh_token');              // encrypted at rest (model cast)
            $table->timestamp('token_expires_at')->nullable();
            $table->string('scope')->nullable();
            $table->string('drive_folder_id')->nullable(); // cached "Tali Khata Backups" folder id
            $table->string('platform')->nullable();         // web | android | ios (which client linked)
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_credentials');
    }
};
