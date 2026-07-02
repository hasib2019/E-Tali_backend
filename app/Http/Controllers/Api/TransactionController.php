<?php

namespace App\Http\Controllers\Api;

use App\Models\Party;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends ApiController
{
    /**
     * List all transactions of a party (latest first).
     */
    public function index(Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $transactions = $party->transactions()
            ->orderByDesc('txn_date')
            ->orderByDesc('id')
            ->get();

        return $this->ok($transactions);
    }

    public function store(Request $request, Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $data = $request->validate([
            // debit = you gave (দিলাম), credit = you got (পেলাম)
            'type' => ['required', 'in:debit,credit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string'],
            'txn_date' => ['nullable', 'date'],
        ]);

        $transaction = $party->transactions()->create([
            ...$data,
            'business_id' => $party->business_id,
            'user_id' => $request->user()->id,
            'txn_date' => $data['txn_date'] ?? now()->toDateString(),
        ]);

        return $this->ok($transaction, 'Transaction saved.', 201);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->ensureOwnsTransaction($transaction);

        $transaction->delete();

        return $this->ok(null, 'Transaction deleted.');
    }
}
