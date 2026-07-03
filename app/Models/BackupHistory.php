<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'drive_file_id', 'file_name', 'size_bytes', 'status', 'type', 'error',
])]
class BackupHistory extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
