<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesMedia;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    use HandlesMedia;

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

        // Don't let a mail-server hiccup fail the registration — the user is
        // created and can trigger "resend" from the verify screen.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

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
     * Update the authenticated user's editable profile fields.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:5000000'],
        ]);

        $user = $request->user();
        $updates = collect($data)->only(['name', 'phone'])->all();

        if (array_key_exists('avatar', $data)) {
            $oldAvatar = $user->avatar;
            if ($oldAvatar && ! str_starts_with($oldAvatar, 'http://')
                && ! str_starts_with($oldAvatar, 'https://')
                && ! str_starts_with($oldAvatar, 'data:')) {
                $this->deleteMedia($oldAvatar);
            }

            $updates['avatar'] = $data['avatar']
                ? $this->storeBase64($data['avatar'], 'avatars', $user->id, 'profile')
                : null;
        }

        $user->update($updates);
        $user->refresh();

        return $this->ok($this->userPayload($user), 'Profile updated.');
    }

    /**
     * Change (or set, for Google-only accounts) the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();
        if ($user->password && (
            empty($data['current_password'])
            || ! Hash::check($data['current_password'], $user->password)
        )) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => $data['password']]);

        return $this->ok(null, 'Password updated.');
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
        $avatar = $user->avatar;
        if ($avatar && ! str_starts_with($avatar, 'http://')
            && ! str_starts_with($avatar, 'https://')
            && ! str_starts_with($avatar, 'data:')) {
            $avatar = $this->mediaDataUri($avatar, 400);
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $avatar,
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
            // Backend-authoritative entitlements the app caches + enforces offline.
            'entitlements' => $user->package
                ? $user->package->entitlements()
                : ['max_businesses' => 1, 'max_parties' => null, 'allowed_categories' => null, 'features' => []],
            'play_store_url' => config('app.play_store_url'),
        ];
    }
}
