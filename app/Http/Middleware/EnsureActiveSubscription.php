<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the protected API behind account + subscription state and returns a
 * machine-readable lock code the mobile app can branch on.
 *
 * Usage:
 *   ->middleware('subscription')                  full lock (expiry enforced)
 *   ->middleware('subscription:ignore_expiry')    allow expired users (e.g. Drive backup/restore)
 */
class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next, string $mode = 'enforce'): Response
    {
        $user = $request->user();

        // Route is behind auth:sanctum already, but guard defensively.
        if (! $user) {
            return $this->lock('UNAUTHENTICATED', 'Authentication required.', 401);
        }

        if (! $user->is_active) {
            return $this->lock(
                'ACCOUNT_INACTIVE',
                'Your account has been deactivated. Please contact support.',
            );
        }

        if (! $user->isVerified()) {
            $viaPhone = $user->verification_method === 'phone';

            return $this->lock(
                $viaPhone ? 'PHONE_UNVERIFIED' : 'EMAIL_UNVERIFIED',
                $viaPhone
                    ? 'Please verify your phone number to continue.'
                    : 'Please verify your email address to continue.',
            );
        }

        if ($mode !== 'ignore_expiry' && ! $user->hasActiveSubscription()) {
            return $this->lock(
                'SUBSCRIPTION_EXPIRED',
                'Your subscription has expired. Please renew to continue.',
            );
        }

        return $next($request);
    }

    private function lock(string $code, string $message, int $status = 403): Response
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
        ], $status);
    }
}
