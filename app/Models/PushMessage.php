<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'body', 'route', 'data', 'audience', 'status',
    'recipient_count', 'sent_count', 'failed_count', 'opened_count', 'sent_at',
])]
class PushMessage extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'audience' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
