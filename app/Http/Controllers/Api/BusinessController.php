<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Support\CategoryRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessController extends ApiController
{
    /**
     * List all businesses owned by the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $businesses = $request->user()->businesses()->latest()->get();

        return $this->ok($businesses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::in(CategoryRegistry::CATEGORIES)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:8'],
            'meta' => ['nullable', 'array'],
        ]);

        $data['category'] = CategoryRegistry::normalize($data['category'] ?? null);

        $business = $request->user()->businesses()->create($data);

        // Seed the category's default income/expense buckets (chips on the cash form).
        foreach (CategoryRegistry::defaultCashCategories($business->category) as $dir => $cats) {
            foreach ($cats as $i => $c) {
                $business->cashCategories()->create([
                    'type' => $dir,
                    'name' => $c['name'],
                    'icon' => $c['icon'],
                    'sort' => $i,
                ]);
            }
        }

        return $this->ok($business, 'Business created.', 201);
    }

    public function show(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        return $this->ok($business);
    }

    public function update(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'category' => ['sometimes', 'string', Rule::in(CategoryRegistry::CATEGORIES)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:8'],
            'meta' => ['nullable', 'array'],
        ]);

        $business->update($data);

        return $this->ok($business, 'Business updated.');
    }

    public function destroy(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $business->delete();

        return $this->ok(null, 'Business deleted.');
    }
}
