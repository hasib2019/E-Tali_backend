<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['business_id', 'name', 'phone', 'type', 'address', 'opening_balance'])]
class Party extends Model
{
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Net balance = opening_balance
     *   + Σ(debit) − Σ(credit)                      (simple cash entries)
     *   + Σ(sale due) − Σ(purchase due).            (vouchers)
     * Positive => party owes you (পাবেন). Negative => you owe party (দিবেন).
     */
    public function balance(): float
    {
        $debit = (float) $this->transactions()->where('type', 'debit')->sum('amount');
        $credit = (float) $this->transactions()->where('type', 'credit')->sum('amount');
        $saleDue = (float) $this->vouchers()->where('type', 'sale')->sum('due_amount');
        $purchaseDue = (float) $this->vouchers()->where('type', 'purchase')->sum('due_amount');

        return round(
            (float) $this->opening_balance + $debit - $credit + $saleDue - $purchaseDue,
            2,
        );
    }
}
