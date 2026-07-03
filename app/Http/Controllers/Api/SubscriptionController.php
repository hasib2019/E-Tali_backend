<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends ApiController
{
    /**
     * Public list of assignable packages, shown on the app's subscription screen.
     */
    public function packages(): JsonResponse
    {
        $packages = Package::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'price', 'duration_days', 'description', 'max_businesses', 'max_parties']);

        return $this->ok($packages);
    }
}
