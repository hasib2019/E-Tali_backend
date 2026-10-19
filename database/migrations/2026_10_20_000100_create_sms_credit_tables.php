<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-facing SMS (tagada / transaction alerts) paid for with credits.
 *
 * - `sms_settings` (singleton): master switch + how many free SMS a new
 *   account starts with. Owner edits it from the Filament "SMS Settings" page.
 * - `users.sms_balance`: credits left, counted in SMS *parts* — a Bangla
 *   message longer than 70 characters is billed as several parts by the
 *   gateway, so it costs several credits here too.
 * - `packages.sms_quota`: credits added every time the package is assigned or
 *   renewed (premium plans carry SMS, free ones carry 0).
 * - `sms_logs`: one row per grant and per send — the audit trail for both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('free_sms_per_user')->default(5);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('sms_balance')->default(0)->after('web_access');
            $table->unsignedInteger('sms_sent_total')->default(0)->after('sms_balance');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('sms_quota')->default(0)->after('max_parties');
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16); // grant | send
            $table->integer('credits'); // +granted / −spent
            $table->string('phone', 20)->nullable();
            $table->text('message')->nullable();
            $table->unsignedSmallInteger('parts')->default(0);
            $table->string('status', 24); // granted | sent | failed
            $table->string('message_id')->nullable()->index();
            $table->string('delivery_status')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('business_name')->nullable();
            $table->string('party_name')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        // Everyone who already has an account gets the same starter credits a
        // new sign-up would.
        DB::table('users')->update(['sms_balance' => 5]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('sms_quota');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sms_balance', 'sms_sent_total']);
        });
        Schema::dropIfExists('sms_settings');
    }
};
