<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends ApiController
{
    /**
     * Public list of assignable packages, shown on the app's subscription screen.
     */
    public function packages(): JsonResponse
    {
        $packages = Package::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'price', 'duration_days', 'description', 'show_icon', 'max_businesses', 'max_parties']);

        return $this->ok($packages);
    }

    /**
     * Self-service Free Trial renewal from the app's "Renew" button: puts the
     * user on the Free Trial for 6 months from now. Expiry is reset, not
     * stacked, so repeated taps never pile up extra months. Only a user whose
     * current plan is free (price 0) may renew — same rule as the app's button.
     */
    public function renewTrial(Request $request, AuthController $auth): JsonResponse
    {
        $user = $request->user()->loadMissing('package');

        abort_unless($user->is_active, 403, 'Your account is inactive.');
        abort_unless($user->isVerified(), 403, 'Please verify your account first.');

        $package = Package::where('name', 'Free Trial')->where('is_active', true)->first();
        abort_unless($package, 404, 'The Free Trial plan is not available.');

        abort_unless(
            $user->package && (float) $user->package->price === 0.0,
            422,
            'Only the Free Trial plan can be renewed here.',
        );

        $startsAt = now();
        $expiresAt = now()->addMonths(6);

        $user->subscriptions()->create([
            'package_id' => $package->id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'amount' => 0,
            'status' => 'active',
            'note' => 'Free trial renewed from app (6 months)',
        ]);

        $user->forceFill([
            'package_id' => $package->id,
            'subscription_status' => 'active',
            'subscribed_at' => $startsAt,
            'subscription_expires_at' => $expiresAt,
            'is_paid' => false,
        ])->save();

        return $this->ok($auth->userPayload($user->unsetRelation('package')), 'Free trial renewed for 6 months.');
    }
}
