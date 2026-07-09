<?php

namespace App\Http\Controllers\Api;

use App\Models\Budget;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends ApiController
{
    /**
     * List the khata's named budget envelopes, each with its planned amount and
     * how much was spent this month (cash-out entries tagged with the budget
     * name). ?month=YYYY-MM (defaults to the current month).
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $month = $request->string('month')->toString();
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        [$year, $mon] = explode('-', $month);

        // Sum this month's cash-out per category once.
        $spentByCategory = $business->cashbookEntries()
            ->where('type', 'cash_out')
            ->whereYear('entry_date', (int) $year)
            ->whereMonth('entry_date', (int) $mon)
            ->get(['category', 'amount'])
            ->groupBy(fn ($e) => $e->category ?: '')
            ->map(fn ($grp) => (float) $grp->sum(fn ($e) => (float) $e->amount));

        $budgets = $business->budgets()
            ->orderBy('name')
            ->get()
            ->map(fn (Budget $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'amount' => $b->amount !== null ? (float) $b->amount : null,
                'spent' => round((float) ($spentByCategory[$b->name] ?? 0), 2),
            ]);

        return $this->ok(['month' => $month, 'budgets' => $budgets]);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $budget = $business->budgets()->create([
            'name' => $data['name'],
            'amount' => $data['amount'] ?? null,
        ]);

        return $this->ok($budget, 'Budget added.', 201);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        $this->ensureOwnsChild($budget);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $budget->update($data);

        return $this->ok($budget->fresh(), 'Budget updated.');
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $this->ensureOwnsChild($budget);

        DB::transaction(function () use ($budget) {
            // Refund the budget's spending back to the balance by removing the
            // cash-out entries tagged with this budget's name.
            $budget->business->cashbookEntries()
                ->where('type', 'cash_out')
                ->where('category', $budget->name)
                ->delete();

            $budget->delete();
        });

        return $this->ok(null, 'Budget removed and its spending refunded.');
    }
}
