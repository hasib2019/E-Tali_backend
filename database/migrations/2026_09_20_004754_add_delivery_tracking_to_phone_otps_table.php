<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_otps', function (Blueprint $table) {
            // Qolek's own id for the send, so the delivery callback can be
            // matched back to this row.
            $table->string('message_id')->nullable()->after('phone');
            // Whatever Qolek's callback reports (e.g. delivered/failed) — kept
            // as the gateway's raw wording rather than an enum, since we
            // haven't seen the real payload shape yet.
            $table->string('delivery_status')->nullable()->after('message_id');
            $table->timestamp('delivered_at')->nullable()->after('delivery_status');

            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('phone_otps', function (Blueprint $table) {
            $table->dropIndex(['message_id']);
            $table->dropColumn(['message_id', 'delivery_status', 'delivered_at']);
        });
    }
};
