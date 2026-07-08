<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\SavingsGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsGoalController extends ApiController
{
    public function index(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        return $this->ok($business->savingsGoals()->orderBy('is_done')->orderByDesc('id')->get());
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'saved_amount' => ['nullable', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
        ]);

        $goal = $business->savingsGoals()->create($data);

        return $this->ok($goal, 'Goal added.', 201);
    }

    public function update(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        $this->ensureOwnsChild($savingsGoal);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'target_amount' => ['sometimes', 'numeric', 'min:0'],
            'saved_amount' => ['sometimes', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'is_done' => ['sometimes', 'boolean'],
        ]);

        $savingsGoal->update($data);

        return $this->ok($savingsGoal->fresh(), 'Goal updated.');
    }

    public function destroy(SavingsGoal $savingsGoal): JsonResponse
    {
        $this->ensureOwnsChild($savingsGoal);

        $savingsGoal->delete();

        return $this->ok(null, 'Goal removed.');
    }
}
