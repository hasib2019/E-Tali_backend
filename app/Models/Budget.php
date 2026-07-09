<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['business_id', 'name', 'period', 'amount'])]
class Budget extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
