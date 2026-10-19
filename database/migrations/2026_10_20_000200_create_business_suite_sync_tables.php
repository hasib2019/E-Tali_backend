<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Server mirror of the device's schema V6 (business suite) so Diamond live
 * sync can carry it: cash sales, discount/VAT/invoice numbers, returns, money
 * accounts + transfers, stock movements, credit limit / due date, staff +
 * salary, and day closing. Money is decimal here, integer paisa on device.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->decimal('credit_limit', 15, 2)->nullable()->after('opening_balance');
            $table->date('due_date')->nullable()->after('credit_limit');
            $table->boolean('is_walk_in')->default(false)->after('due_date');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('low_stock', 15, 3)->nullable()->after('stock');
            $table->string('barcode', 64)->nullable()->after('low_stock');
        });

        // Returns add sale_return / purchase_return, so the type can no longer be an enum.
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('type', 20)->change();
            $table->string('invoice_no', 40)->nullable()->after('type');
            $table->decimal('discount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('vat', 15, 2)->default(0)->after('discount');
            $table->string('customer_name')->nullable()->after('note');
        });

        Schema::table('voucher_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->nullable()->after('unit_price');
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind', 20)->default('other');
            $table->string('number', 40)->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->integer('sort')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->index('business_id');
        });

        Schema::table('cashbook_entries', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('business_id')->constrained('accounts')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->after('account_id')->constrained('vouchers')->cascadeOnDelete();
        });

        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_account_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('fee', 15, 2)->default(0);
            $table->date('transfer_date');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'transfer_date']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 20)->default('adjust');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->date('movement_date');
            $table->timestamps();
            $table->index(['product_id', 'movement_date']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('designation')->nullable();
            $table->decimal('monthly_salary', 15, 2)->default(0);
            $table->date('joined_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('business_id');
        });

        Schema::create('employee_entries', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashbook_entry_id')->nullable()->constrained('cashbook_entries')->nullOnDelete();
            $table->string('kind', 20);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('period', 7);
            $table->date('entry_date');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'period']);
        });

        Schema::create('day_closings', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id', 64)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('close_date');
            $table->decimal('expected_cash', 15, 2)->default(0);
            $table->decimal('counted_cash', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->decimal('cash_in', 15, 2)->default(0);
            $table->decimal('cash_out', 15, 2)->default(0);
            $table->decimal('sales', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'close_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closings');
        Schema::dropIfExists('employee_entries');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('account_transfers');
        Schema::table('cashbook_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropConstrainedForeignId('account_id');
        });
        Schema::dropIfExists('accounts');
        Schema::table('voucher_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'discount', 'vat', 'customer_name']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['low_stock', 'barcode']);
        });
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'due_date', 'is_walk_in']);
        });
    }
};
