<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('route')->nullable();      // in-app deep link on tap (e.g. "/subscription")
            $table->json('data')->nullable();          // extra payload for the tap handler
            $table->json('audience')->nullable();      // {type: all|category|subscription|users, values: [...]}
            $table->string('status')->default('draft'); // draft | queued | sent | failed
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_messages');
    }
};
