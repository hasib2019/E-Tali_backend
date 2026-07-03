<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('drive_file_id')->nullable();
            $table->string('file_name');
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('status')->default('pending');  // pending | success | failed
            $table->string('type')->default('manual');      // manual | scheduled
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_histories');
    }
};
