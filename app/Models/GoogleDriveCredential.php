<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'access_token', 'refresh_token', 'token_expires_at',
    'scope', 'drive_folder_id', 'platform', 'connected_at',
])]
#[Hidden(['access_token', 'refresh_token'])]
class GoogleDriveCredential extends Model
{
    protected function casts(): array
    {
        return [
            // Tokens are encrypted at rest using the app key.
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
