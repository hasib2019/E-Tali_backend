<?php

namespace App\Http\Controllers\Api;

use App\Models\Batch;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends ApiController
{
    public function index(Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $batches = $business->batches()
            ->withCount('parties')
            ->orderBy('name')
            ->get();

        return $this->ok($batches);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'schedule' => ['nullable', 'string', 'max:120'],
        ]);

        $batch = $business->batches()->create($data);

        return $this->ok($batch, 'Batch created.', 201);
    }

    public function update(Request $request, Batch $batch): JsonResponse
    {
        $this->ensureOwnsChild($batch);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'schedule' => ['nullable', 'string', 'max:120'],
        ]);

        $batch->update($data);

        return $this->ok($batch->fresh(), 'Batch updated.');
    }

    public function destroy(Batch $batch): JsonResponse
    {
        $this->ensureOwnsChild($batch);

        $batch->delete();

        return $this->ok(null, 'Batch deleted.');
    }
}
