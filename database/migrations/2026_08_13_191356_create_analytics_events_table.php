<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('screen');                 // route/path, e.g. "/report" or "cashbook"
            $table->string('event_type')->default('screen_view'); // screen_view | action | open
            $table->string('platform')->nullable();   // ios | android | web
            $table->string('app_version')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index('screen');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
