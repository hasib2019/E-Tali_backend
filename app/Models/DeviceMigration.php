<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'status', 'checksum', 'counts', 'device_id', 'exported_at', 'confirmed_at'])]
class DeviceMigration extends Model
{
    protected function casts(): array
    {
        return [
            'counts' => 'array',
            'exported_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
