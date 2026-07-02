<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:8'],
        ]);

        $business = $request->user()->businesses()->create($data);

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
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:8'],
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
