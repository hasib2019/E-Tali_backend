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
    'name', 'email', 'phone', 'password',
    'is_active', 'provider', 'google_id', 'avatar',
    'package_id', 'subscription_status', 'subscribed_at', 'subscription_expires_at', 'is_paid',
    'backup_frequency', 'last_backup_at',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_paid' => 'boolean',
            'subscribed_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'last_backup_at' => 'datetime',
        ];
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
}
