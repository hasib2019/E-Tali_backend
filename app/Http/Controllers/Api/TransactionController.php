<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesMedia;
use App\Models\Party;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends ApiController
{
    use HandlesMedia;

    /**
     * List all transactions of a party (latest first).
     */
    public function index(Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $transactions = $party->transactions()
            ->orderByDesc('txn_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Transaction $t) => $this->present($t));

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
            'image' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $transaction = $party->transactions()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'business_id' => $party->business_id,
            'user_id' => $request->user()->id,
            'txn_date' => $data['txn_date'] ?? now()->toDateString(),
        ]);

        $this->attachMedia($transaction, $data);

        return $this->ok($this->present($transaction, withMedia: true), 'Transaction saved.', 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $this->ensureOwnsTransaction($transaction);

        return $this->ok($this->present($transaction, withMedia: true));
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $this->ensureOwnsTransaction($transaction);

        $data = $request->validate([
            'type' => ['sometimes', 'in:debit,credit'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string'],
            'txn_date' => ['nullable', 'date'],
            'image' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $transaction->update(
            array_intersect_key($data, array_flip(['type', 'amount', 'note', 'txn_date'])),
        );

        $this->attachMedia($transaction, $data);

        return $this->ok($this->present($transaction->fresh(), withMedia: true), 'Transaction updated.');
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->ensureOwnsTransaction($transaction);

        $this->deleteMedia($transaction->image_path, $transaction->signature_path);
        $transaction->delete();

        return $this->ok(null, 'Transaction deleted.');
    }

    /** Persist any supplied base64 image/signature onto the transaction. */
    private function attachMedia(Transaction $transaction, array $data): void
    {
        $updates = [];
        if (! empty($data['image'])) {
            $updates['image_path'] = $this->storeBase64($data['image'], 'transaction', $transaction->id, 'image');
        }
        if (! empty($data['signature'])) {
            $updates['signature_path'] = $this->storeBase64($data['signature'], 'transaction', $transaction->id, 'signature');
        }
        if ($updates) {
            $transaction->update($updates);
        }
    }

    /** Transaction as array; embeds media as data URIs when requested. */
    private function present(Transaction $transaction, bool $withMedia = false): array
    {
        $arr = $transaction->toArray();
        $arr['has_image'] = (bool) $transaction->image_path;
        $arr['has_signature'] = (bool) $transaction->signature_path;
        if ($withMedia) {
            $arr['image_url'] = $this->mediaDataUri($transaction->image_path);
            $arr['signature_url'] = $this->mediaDataUri($transaction->signature_path);
        }

        return $arr;
    }
}
