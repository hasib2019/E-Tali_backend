<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'backup_uuid', 'file_name', 'storage_path',
    'size_bytes', 'checksum', 'drive_file_id', 'source',
])]
class EncryptedBackup extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
