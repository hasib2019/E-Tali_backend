<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'type', 'credits', 'phone', 'message', 'parts', 'status',
    'message_id', 'delivery_status', 'delivered_at', 'business_name',
    'party_name', 'note', 'admin_id',
])]
class SmsLog extends Model
{
    public const TYPE_GRANT = 'grant';

    public const TYPE_SEND = 'send';

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'parts' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
