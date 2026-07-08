<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal-finance building blocks used by the salaried / student (and partly
 * teacher) categories. All brand-new tables — nothing touches existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Named income/expense buckets shown as chips on the cash entry form.
        Schema::create('cash_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type');            // 'in' | 'out'
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['business_id', 'type']);
        });

        // One monthly spending target per khata per month (YYYY-MM).
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);       // YYYY-MM
            $table->decimal('amount', 14, 2);
            $table->timestamps();
            $table->unique(['business_id', 'period']);
        });

        // Savings goals (target + how much saved so far).
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('target_amount', 14, 2);
            $table->decimal('saved_amount', 14, 2)->default(0);
            $table->date('target_date')->nullable();
            $table->boolean('is_done')->default(false);
            $table->timestamps();
            $table->index('business_id');
        });

        // Bill / fee reminders.
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 14, 2)->nullable();
            $table->date('due_date');
            $table->boolean('is_done')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('savings_goals');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('cash_categories');
    }
};
