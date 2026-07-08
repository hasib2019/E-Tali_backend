<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\CashCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashCategoryController extends ApiController
{
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $categories = $business->cashCategories()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return $this->ok($categories);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'type' => ['required', 'in:in,out'],
            'name' => ['required', 'string', 'max:60'],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort' => ['nullable', 'integer'],
        ]);

        $category = $business->cashCategories()->create($data);

        return $this->ok($category, 'Category added.', 201);
    }

    public function update(Request $request, CashCategory $cashCategory): JsonResponse
    {
        $this->ensureOwnsChild($cashCategory);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort' => ['nullable', 'integer'],
        ]);

        $cashCategory->update($data);

        return $this->ok($cashCategory->fresh(), 'Category updated.');
    }

    public function destroy(CashCategory $cashCategory): JsonResponse
    {
        $this->ensureOwnsChild($cashCategory);

        $cashCategory->delete();

        return $this->ok(null, 'Category deleted.');
    }
}
