<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'token', 'business_name', 'party_name', 'payload', 'views', 'expires_at'])]
class SharedStatement extends Model
{
    use Prunable;

    /** How long a shared link stays open. */
    public const LIFETIME_DAYS = 30;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Expired links are deleted by `model:prune`. */
    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now());
    }
}
