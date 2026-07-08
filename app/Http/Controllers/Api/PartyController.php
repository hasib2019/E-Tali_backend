<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends ApiController
{
    /**
     * List parties of a business, with computed balance.
     * Filters: ?type=customer|supplier & ?search=name/phone
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $parties = $business->parties()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
            })
            ->withSum(['transactions as debit_total' => fn ($q) => $q->where('type', 'debit')], 'amount')
            ->withSum(['transactions as credit_total' => fn ($q) => $q->where('type', 'credit')], 'amount')
            ->withSum(['vouchers as sale_due' => fn ($q) => $q->where('type', 'sale')], 'due_amount')
            ->withSum(['vouchers as purchase_due' => fn ($q) => $q->where('type', 'purchase')], 'due_amount')
            ->orderBy('name')
            ->get()
            ->map(function (Party $p) {
                $balance = round(
                    (float) $p->opening_balance
                    + (float) $p->debit_total - (float) $p->credit_total
                    + (float) $p->sale_due - (float) $p->purchase_due,
                    2,
                );
                $data = $p->toArray();
                $data['balance'] = $balance;

                return $data;
            });

        return $this->ok($parties);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', 'in:customer,supplier'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'roll' => ['nullable', 'string', 'max:40'],
        ]);

        $party = $business->parties()->create($data);

        return $this->ok($party, 'Party created.', 201);
    }

    public function show(Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $data = $party->toArray();
        $data['balance'] = $party->balance();

        return $this->ok($data);
    }

    public function update(Request $request, Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'type' => ['sometimes', 'required', 'in:customer,supplier'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'roll' => ['nullable', 'string', 'max:40'],
        ]);

        $party->update($data);

        return $this->ok($party, 'Party updated.');
    }

    public function destroy(Party $party): JsonResponse
    {
        $this->ensureOwnsParty($party);

        $party->delete();

        return $this->ok(null, 'Party deleted.');
    }
}
