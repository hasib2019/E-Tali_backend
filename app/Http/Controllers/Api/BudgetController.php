<?php

namespace App\Http\Controllers\Api;

use App\Models\Budget;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends ApiController
{
    public function index(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        return $this->ok($business->budgets()->orderByDesc('period')->get());
    }

    /** Upsert the budget for a given month (YYYY-MM). */
    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $budget = $business->budgets()->updateOrCreate(
            ['period' => $data['period']],
            ['amount' => $data['amount']],
        );

        return $this->ok($budget, 'Budget saved.');
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $this->ensureOwnsChild($budget);

        $budget->delete();

        return $this->ok(null, 'Budget removed.');
    }
}
