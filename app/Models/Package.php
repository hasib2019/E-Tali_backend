<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'price', 'duration_days', 'description', 'max_businesses', 'max_parties', 'allowed_categories', 'features', 'is_active'])]
class Package extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'allowed_categories' => 'array',
            'features' => 'array',
        ];
    }

    /**
     * The entitlement block the app enforces (backend is authority).
     * null max_businesses = unlimited; null/[] allowed_categories = all.
     */
    public function entitlements(): array
    {
        return [
            'max_businesses' => $this->max_businesses,
            'max_parties' => $this->max_parties,
            'allowed_categories' => $this->allowed_categories ?: null,
            'features' => $this->features ?? [],
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
