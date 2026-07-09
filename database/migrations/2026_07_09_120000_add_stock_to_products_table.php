<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory for the Business category: current stock quantity + an optional
 * product category. Additive & nullable/defaulted — existing products keep
 * working (stock starts at 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 12, 2)->default(0)->after('purchase_price');
            $table->string('category')->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock', 'category']);
        });
    }
};
