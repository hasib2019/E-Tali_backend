<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends AuthController
{
    /**
     * Sign in (or register) with a Google id_token obtained by the app.
     *
     * We verify the token's signature + issuer with Google's certs, then check
     * the `aud` against our own web/android/ios client IDs. A verified Google
     * email is treated as verified in our system (no email step required).
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $clientIds = array_values(array_filter([
            config('services.google.web_client_id'),
            config('services.google.android_client_id'),
            config('services.google.ios_client_id'),
        ]));

        if (empty($clientIds)) {
            return response()->json([
                'success' => false,
                'code' => 'GOOGLE_NOT_CONFIGURED',
                'message' => 'Google sign-in is not configured on the server.',
            ], 503);
        }

        // verifyIdToken() with no configured client id checks signature + issuer
        // only; we enforce the audience against our client IDs ourselves.
        // Wrap it: a cert-fetch/parse failure must not 500 the request.
        try {
            $payload = (new GoogleClient())->verifyIdToken($data['id_token']);
        } catch (\Throwable $e) {
            report($e);
            $payload = false;
        }

        $audienceOk = $payload && in_array($payload['aud'] ?? null, $clientIds, true);
        $emailVerified = $payload && filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $payload || ! $audienceOk || ! $emailVerified || empty($payload['email'])) {
            throw ValidationException::withMessages([
                'id_token' => ['Could not verify your Google account.'],
            ]);
        }

        $user = $this->findOrCreateGoogleUser($payload);

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Match by google_id, else link an existing same-email account, else create.
     */
    private function findOrCreateGoogleUser(array $payload): User
    {
        $googleId = $payload['sub'];
        $email = $payload['email'];

        $user = User::where('google_id', $googleId)->first();
        if ($user) {
            return $user;
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            // Link Google to the existing account and treat the email as verified.
            $user->forceFill([
                'google_id' => $googleId,
                'provider' => $user->provider === 'email' ? 'email' : 'google',
                'avatar' => $user->avatar ?: ($payload['picture'] ?? null),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        return User::create([
            'name' => $payload['name'] ?? $email,
            'email' => $email,
            'google_id' => $googleId,
            'provider' => 'google',
            'avatar' => $payload['picture'] ?? null,
            'password' => null,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }
}
