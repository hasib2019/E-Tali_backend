<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    /**
     * Register a new user and return an API token.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create($data);
        $user->refresh(); // hydrate DB defaults (is_active=true, provider='email', ...)
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 'Registration successful. Please verify your email.', 201);
    }

    /**
     * Log in with email + password and return an API token.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Return the currently authenticated user with account + subscription state.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->ok($this->userPayload($request->user()));
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logged out.');
    }

    /**
     * Shared user representation returned by register / login / me.
     * This is the single object the mobile app's auth guard trusts.
     */
    public function userPayload(User $user): array
    {
        $user->loadMissing('package');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'provider' => $user->provider,
            'email_verified' => $user->hasVerifiedEmail(),
            'is_active' => (bool) $user->is_active,
            'package' => $user->package,
            'subscription_status' => $user->subscription_status,
            'subscription_expires_at' => $user->subscription_expires_at?->toIso8601String(),
            'has_active_subscription' => $user->hasActiveSubscription(),
            'backup_frequency' => $user->backup_frequency,
            'last_backup_at' => $user->last_backup_at?->toIso8601String(),
            'drive_connected' => Schema::hasTable('google_drive_credentials')
                && $user->driveCredential()->exists(),
        ];
    }
}
