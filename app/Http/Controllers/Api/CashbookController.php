<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\CashbookEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookController extends ApiController
{
    /**
     * List cashbook entries of a business.
     * Filters: ?type=cash_in|cash_out & ?from=YYYY-MM-DD & ?to=YYYY-MM-DD
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $entries = $business->cashbookEntries()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date('to')))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        return $this->ok($entries);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
            'entry_date' => ['nullable', 'date'],
        ]);

        $entry = $business->cashbookEntries()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
        ]);

        return $this->ok($entry, 'Cashbook entry saved.', 201);
    }

    public function destroy(CashbookEntry $cashbookEntry): JsonResponse
    {
        $this->ensureOwnsCashbookEntry($cashbookEntry);

        $cashbookEntry->delete();

        return $this->ok(null, 'Entry deleted.');
    }
}
