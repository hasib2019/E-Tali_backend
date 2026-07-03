<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Account state
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('provider')->default('email')->after('is_active'); // email | google
            $table->string('google_id')->nullable()->unique()->after('provider');
            $table->string('avatar')->nullable()->after('google_id');

            // Subscription snapshot (denormalized for fast /me + middleware checks;
            // the audit trail lives in the `subscriptions` table).
            $table->foreignId('package_id')->nullable()->after('avatar')
                ->constrained('packages')->nullOnDelete();
            $table->string('subscription_status')->default('none')->after('package_id'); // none | active | expired
            $table->timestamp('subscribed_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscribed_at');
            $table->boolean('is_paid')->default(false)->after('subscription_expires_at');

            // Google Drive backup preferences
            $table->string('backup_frequency')->default('off')->after('is_paid'); // off | daily | weekly | monthly
            $table->timestamp('last_backup_at')->nullable()->after('backup_frequency');
        });

        // Google-only users have no password.
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn([
                'is_active', 'provider', 'google_id', 'avatar',
                'subscription_status', 'subscribed_at', 'subscription_expires_at', 'is_paid',
                'backup_frequency', 'last_backup_at',
            ]);
        });
    }
};
