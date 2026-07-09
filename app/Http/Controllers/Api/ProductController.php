<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    /**
     * List a business's product catalog. Filter with ?search=
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $products = $business->products()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where('name', 'like', "%{$s}%");
            })
            ->orderBy('name')
            ->get();

        return $this->ok($products);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:30'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'numeric'],
            'category' => ['nullable', 'string', 'max:60'],
        ]);

        $product = $business->products()->create($data);

        return $this->ok($product, 'Product saved.', 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->ensureOwnsProduct($product);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:30'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'numeric'],
            'category' => ['nullable', 'string', 'max:60'],
        ]);

        $product->update($data);

        return $this->ok($product, 'Product updated.');
    }

    /**
     * Add to (or remove from) a product's stock — powers the "Restock" button.
     * `change` is a delta (positive to restock, negative to correct down).
     */
    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $this->ensureOwnsProduct($product);

        $data = $request->validate([
            'change' => ['required', 'numeric'],
        ]);

        $product->increment('stock', $data['change']);

        return $this->ok($product->fresh(), 'Stock updated.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->ensureOwnsProduct($product);

        $product->delete();

        return $this->ok(null, 'Product deleted.');
    }
}
