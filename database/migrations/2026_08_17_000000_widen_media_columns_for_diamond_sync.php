<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diamond-tier live sync pushes the device's media columns verbatim — on
 * device these are full inline base64 data URIs (not a file path, despite an
 * older schema comment), which a VARCHAR(255) column truncates. `vouchers`
 * is deliberately NOT touched here: its image_path/signature_path already
 * hold a real storage-disk file path written by VoucherController::
 * storeBase64(), a live contract the sync ingestion path preserves rather
 * than widens (see LedgerSyncService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
            $table->longText('signature_path')->nullable()->change();
        });

        Schema::table('cashbook_entries', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
            $table->longText('signature_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
            $table->string('signature_path')->nullable()->change();
        });

        Schema::table('cashbook_entries', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
            $table->string('signature_path')->nullable()->change();
        });
    }
};
