<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('app_feedback')]
#[Fillable(['user_id', 'message', 'rating', 'app_version', 'platform', 'status', 'admin_note'])]
class AppFeedback extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
