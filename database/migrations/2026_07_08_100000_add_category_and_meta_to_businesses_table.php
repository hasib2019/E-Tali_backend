<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a "category" lens to each khata (business/salaried/student/teacher…)
     * plus a flexible per-category settings bag. Additive & nullable:
     * every existing row keeps working and defaults to 'business'.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('category')->default('business')->after('type');
            $table->json('meta')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['category', 'meta']);
        });
    }
};
