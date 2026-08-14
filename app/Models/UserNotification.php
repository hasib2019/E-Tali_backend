<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'push_message_id', 'title', 'body', 'route', 'data', 'read_at', 'opened_at',
])]
class UserNotification extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
