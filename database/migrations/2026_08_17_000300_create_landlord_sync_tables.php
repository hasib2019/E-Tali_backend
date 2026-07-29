<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landlord_properties', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('kind')->default('building');
            $table->timestamps();
            $table->index('business_id');
        });

        Schema::create('landlord_units', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('landlord_properties')->cascadeOnDelete();
            $table->string('name');
            $table->string('floor')->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->decimal('monthly_rent', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['business_id', 'property_id']);
        });

        Schema::create('landlord_charges', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('landlord_properties')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('landlord_units')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('period', 7);
            $table->string('category');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'period', 'party_id']);
        });

        Schema::create('landlord_payments', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('landlord_units')->nullOnDelete();
            $table->foreignId('cashbook_entry_id')->nullable()->constrained('cashbook_entries')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('paid_at');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'paid_at']);
        });

        Schema::create('landlord_projects', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('landlord_properties')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('budget', 12, 2)->nullable();
            $table->date('started_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['business_id', 'property_id']);
        });

        Schema::create('landlord_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('payment_id')->constrained('landlord_payments')->cascadeOnDelete();
            $table->foreignId('charge_id')->constrained('landlord_charges')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->index(['payment_id', 'charge_id']);
        });

        Schema::create('landlord_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('landlord_properties')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('landlord_units')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('landlord_projects')->nullOnDelete();
            $table->foreignId('cashbook_entry_id')->nullable()->constrained('cashbook_entries')->nullOnDelete();
            $table->string('category');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('paid_at');
            $table->string('payee')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'paid_at', 'category']);
        });

        Schema::create('landlord_recoveries', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('landlord_projects')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('landlord_payments')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('recovered_at');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_recoveries');
        Schema::dropIfExists('landlord_expenses');
        Schema::dropIfExists('landlord_payment_allocations');
        Schema::dropIfExists('landlord_projects');
        Schema::dropIfExists('landlord_payments');
        Schema::dropIfExists('landlord_charges');
        Schema::dropIfExists('landlord_units');
        Schema::dropIfExists('landlord_properties');
    }
};
