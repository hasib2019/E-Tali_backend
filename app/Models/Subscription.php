<?php

namespace App\Models;

use App\Services\Sms\SmsCreditService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'package_id', 'starts_at', 'expires_at',
    'amount', 'status', 'note', 'created_by_admin_id',
])]
class Subscription extends Model
{
    /**
     * Every assignment / renewal of a package that carries SMS adds its quota
     * to the user's balance — covers the admin action and self-renewal alike.
     */
    protected static function booted(): void
    {
        static::created(function (Subscription $subscription): void {
            $quota = (int) Package::whereKey($subscription->package_id)->value('sms_quota');
            $user = $subscription->user;
            if ($quota > 0 && $user) {
                app(SmsCreditService::class)->grant(
                    $user,
                    $quota,
                    'Package: '.$subscription->package?->name,
                    $subscription->created_by_admin_id,
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}
