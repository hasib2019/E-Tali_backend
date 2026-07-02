<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id', 'party_id', 'user_id', 'type', 'voucher_date',
    'total_amount', 'paid_amount', 'due_amount', 'note',
    'image_path', 'signature_path',
])]
class Voucher extends Model
{
    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(VoucherItem::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Signed effect of this voucher on the party balance
     * (positive = party owes you / পাবেন).
     * sale    -> customer owes the unpaid part  => +due
     * purchase-> you owe supplier the unpaid part => -due
     */
    public function balanceEffect(): float
    {
        return $this->type === 'sale' ? (float) $this->due_amount : -(float) $this->due_amount;
    }
}
