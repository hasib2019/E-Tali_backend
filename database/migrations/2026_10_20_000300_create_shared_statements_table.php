<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A read-only copy of one party's statement that the owner shares as a link
 * (/s/{token}). The ledger lives on the device, so the app uploads the
 * already-computed statement; the link expires and is pruned afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 48)->unique();
            $table->string('business_name');
            $table->string('party_name');
            $table->json('payload');
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_statements');
    }
};
