<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['business_id', 'name', 'schedule'])]
class Batch extends Model
{
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }
}
