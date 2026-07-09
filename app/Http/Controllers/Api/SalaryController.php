<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Support\CategoryRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryController extends ApiController
{
    /**
     * Add this month's salary/allowance as income (a cash-in entry), using the
     * amount saved on the khata (business.meta.monthly_salary). Blocks a second
     * add in the same month.
     */
    public function add(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $salary = (float) data_get($business->meta, 'monthly_salary', 0);
        if ($salary <= 0) {
            return response()->json(['success' => false, 'message' => 'Set your monthly salary first.'], 422);
        }

        $label = $business->category === CategoryRegistry::STUDENT ? 'Allowance' : 'Salary';
        $month = now()->format('Y-m');

        $already = $business->cashbookEntries()
            ->where('type', 'cash_in')
            ->where('category', $label)
            ->whereYear('entry_date', now()->year)
            ->whereMonth('entry_date', now()->month)
            ->exists();
        if ($already) {
            return response()->json(['success' => false, 'message' => "This month's {$label} is already added."], 422);
        }

        $entry = $business->cashbookEntries()->create([
            'user_id' => $request->user()->id,
            'type' => 'cash_in',
            'amount' => $salary,
            'category' => $label,
            'note' => "{$label} {$month}",
            'entry_date' => now()->toDateString(),
        ]);

        return $this->ok($entry, "{$label} added.", 201);
    }
}
