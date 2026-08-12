<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\MessEntry;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessController extends ApiController
{
    /**
     * Full month sheet: meal rate + per-member meals, contribution, cost, balance.
     *   meal_rate = total bazar ÷ total meals
     *   member cost = meals × rate
     *   member given = their deposits + bazar they paid with own money
     *   member balance = given − cost   (+ gets back, − owes)
     *   fund = deposits − bazar paid from the fund (unattributed)
     */
    public function sheet(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);
        $month = $this->month($request);

        $members = $business->parties()->where('type', 'customer')->orderBy('name')->get();
        $meals = $business->meals()->where('period', $month)->get()->keyBy('party_id');
        $entries = $business->messEntries()->where('period', $month)->get();
        $deposits = $entries->where('kind', 'deposit');
        $bazars = $entries->where('kind', 'bazar');

        $totalBazar = round((float) $bazars->sum(fn ($e) => (float) $e->amount), 2);
        $totalMeals = round((float) $meals->sum(fn ($m) => (float) $m->count), 2);
        $rate = $totalMeals > 0 ? round($totalBazar / $totalMeals, 2) : 0.0;
        $totalDeposit = round((float) $deposits->sum(fn ($e) => (float) $e->amount), 2);
        $fundBazar = (float) $bazars->whereNull('party_id')->sum(fn ($e) => (float) $e->amount);
        $fund = round($totalDeposit - $fundBazar, 2);

        $rows = $members->map(function (Party $m) use ($meals, $deposits, $bazars, $rate) {
            $meal = (float) ($meals->get($m->id)->count ?? 0);
            $given = (float) $deposits->where('party_id', $m->id)->sum(fn ($e) => (float) $e->amount)
                + (float) $bazars->where('party_id', $m->id)->sum(fn ($e) => (float) $e->amount);
            $cost = round($meal * $rate, 2);

            return [
                'party_id' => $m->id,
                'name' => $m->name,
                'meals' => round($meal, 2),
                'given' => round($given, 2),
                'cost' => $cost,
                'balance' => round($given - $cost, 2),
            ];
        })->values();

        return $this->ok([
            'month' => $month,
            'meal_rate' => $rate,
            'total_bazar' => $totalBazar,
            'total_meals' => $totalMeals,
            'total_deposit' => $totalDeposit,
            'fund' => $fund,
            'members' => $members->count(),
            'rows' => $rows,
        ]);
    }

    /** Set a member's meal count for the month (absolute value). */
    public function setMeal(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'count' => ['required', 'numeric', 'min:0'],
        ]);
        $this->ensureMember($business, $data['party_id']);

        $meal = $business->meals()->updateOrCreate(
            ['party_id' => $data['party_id'], 'period' => $data['period']],
            ['count' => $data['count']],
        );

        return $this->ok($meal, 'Meal updated.');
    }

    /** A member puts money into the fund. */
    public function deposit(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $this->ensureMember($business, $data['party_id']);

        $entry = $business->messEntries()->create([
            'party_id' => $data['party_id'],
            'period' => $data['period'],
            'kind' => 'deposit',
            'amount' => $data['amount'],
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
            'note' => $data['note'] ?? null,
        ]);

        return $this->ok($entry, 'Deposit added.', 201);
    }

    /** Grocery spend. If party_id is set it was paid by that member (own money). */
    public function bazar(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        if (! empty($data['party_id'])) {
            $this->ensureMember($business, $data['party_id']);
        }

        $entry = $business->messEntries()->create([
            'party_id' => $data['party_id'] ?? null,
            'period' => $data['period'],
            'kind' => 'bazar',
            'amount' => $data['amount'],
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
            'note' => $data['note'] ?? null,
        ]);

        return $this->ok($entry, 'Bazar added.', 201);
    }

    /** List deposit / bazar entries for a month. ?kind=deposit|bazar */
    public function entries(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);
        $month = $this->month($request);

        $list = $business->messEntries()
            ->where('period', $month)
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->string('kind')))
            ->with('party:id,name')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MessEntry $e) => [
                'id' => $e->id,
                'party_id' => $e->party_id,
                'party_name' => $e->party?->name,
                'kind' => $e->kind,
                'amount' => (float) $e->amount,
                'entry_date' => $e->entry_date?->toDateString(),
                'note' => $e->note,
            ]);

        return $this->ok($list);
    }

    public function removeEntry(MessEntry $messEntry): JsonResponse
    {
        $this->ensureOwnsChild($messEntry);

        $messEntry->delete();

        return $this->ok(null, 'Entry removed.');
    }

    private function month(Request $request): string
    {
        $month = $request->string('month')->toString();

        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m');
    }

    private function ensureMember(Business $business, int $partyId): void
    {
        $party = Party::findOrFail($partyId);
        abort_unless($party->business_id === $business->id, 403, 'This member does not belong to you.');
    }
}
