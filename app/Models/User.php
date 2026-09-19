<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'phone', 'verification_method', 'password', 'email_verified_at', 'phone_verified_at',
    'is_active', 'provider', 'google_id', 'avatar',
    'package_id', 'subscription_status', 'subscribed_at', 'subscription_expires_at', 'is_paid',
    'backup_frequency', 'last_backup_at', 'last_active_at', 'last_screen', 'migrated_at',
    'web_access',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_paid' => 'boolean',
            'web_access' => 'boolean',
            'subscribed_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'last_backup_at' => 'datetime',
            'last_active_at' => 'datetime',
            'migrated_at' => 'datetime',
        ];
    }

    /**
     * Registered push devices (Expo tokens) for this user.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * SMS OTP codes issued for this user (phone verification, etc.).
     */
    public function phoneOtps(): HasMany
    {
        return $this->hasMany(PhoneOtp::class);
    }

    /**
     * Feedback this user has sent us about the app.
     */
    public function appFeedback(): HasMany
    {
        return $this->hasMany(AppFeedback::class);
    }

    /**
     * Short-lived "forgot password" codes sent over email or SMS.
     */
    public function passwordResetCodes(): HasMany
    {
        return $this->hasMany(PasswordResetCode::class);
    }

    /**
     * In-app notification inbox rows for this user.
     */
    public function notificationsInbox(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * All businesses (প্রতিষ্ঠান) owned by this user.
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * The package currently assigned to this user (denormalized snapshot).
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Full history of package assignments / renewals.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * The user's stored Google Drive OAuth credentials, if they linked Drive.
     */
    public function driveCredential(): HasOne
    {
        return $this->hasOne(GoogleDriveCredential::class);
    }

    /**
     * Backup attempts (manual + scheduled), newest first when eager-loaded.
     */
    public function backupHistories(): HasMany
    {
        return $this->hasMany(BackupHistory::class);
    }

    /**
     * True when the user has a paid subscription that has not yet expired.
     * The expiry date is authoritative (status string may be stale).
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_expires_at !== null
            && $this->subscription_expires_at->isFuture();
    }

    /**
     * True once the user's phone has been confirmed via SMS OTP.
     */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * True once the user has confirmed their account via EITHER channel.
     * `verification_method` only decides which flow they're routed through
     * while unverified — it is not the source of truth for this check.
     */
    public function isVerified(): bool
    {
        return $this->hasVerifiedEmail() || $this->hasVerifiedPhone();
    }

    /**
     * Put a freshly-verified user on the Free Trial package so they can start
     * using the app immediately. No-op if they already have (or had) any
     * subscription — so it never overwrites a paid plan or grants twice.
     */
    public function grantFreeTrialIfEligible(): void
    {
        if ($this->subscription_expires_at !== null || $this->subscriptions()->exists()) {
            return;
        }

        $package = Package::where('name', 'Free Trial')->where('is_active', true)->first();
        if (! $package) {
            return;
        }

        $startsAt = now();
        $expiresAt = now()->addDays($package->duration_days);

        $this->subscriptions()->create([
            'package_id' => $package->id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'amount' => 0,
            'status' => 'active',
            'note' => 'Auto free trial on email verification',
        ]);

        $this->forceFill([
            'package_id' => $package->id,
            'subscription_status' => 'active',
            'subscribed_at' => $startsAt,
            'subscription_expires_at' => $expiresAt,
            'is_paid' => false,
        ])->save();
    }
}
