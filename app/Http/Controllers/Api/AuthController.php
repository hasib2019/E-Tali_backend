<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesMedia;
use App\Models\User;
use App\Services\PhoneOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    use HandlesMedia;

    /**
     * Register a new user and return an API token.
     */
    public function register(Request $request): JsonResponse
    {
        // Normalize before validating: the uniqueness + format checks below
        // must see the same shape we're about to store, or two spellings of
        // the same number (01712345678 vs +8801712345678) both pass "unique"
        // and collide at insert time instead of failing validation cleanly.
        $rawPhone = $request->input('phone');
        if (is_string($rawPhone) && $rawPhone !== '') {
            $request->merge(['phone' => $this->normalizeBdPhone($rawPhone)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => [
                'required', 'string',
                'regex:/^8801[3-9]\d{8}$/', // normalized Bangladeshi mobile number
                'unique:users,phone',
            ],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'verification_method' => ['required', 'in:email,phone'],
        ]);

        $user = User::create($data);
        $user->refresh(); // hydrate DB defaults (is_active=true, provider='email', ...)

        // Don't let a mail/SMS hiccup fail the registration — the user is
        // created either way and can trigger "resend" from the verify screen.
        try {
            if ($user->verification_method === 'phone') {
                app(PhoneOtpService::class)->send($user);
            } else {
                $user->sendEmailVerificationNotification();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], $user->verification_method === 'phone'
            ? 'Registration successful. Please verify your phone number.'
            : 'Registration successful. Please verify your email.', 201);
    }

    /**
     * Normalize a Bangladeshi mobile number to the gateway's expected shape
     * (880XXXXXXXXXX, no leading +/0) so storage + SMS sending always agree.
     */
    private function normalizeBdPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '880'.substr($digits, 1);
        }

        return '880'.$digits;
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
        $user = $request->user();

        $rawPhone = $request->input('phone');
        if (is_string($rawPhone) && $rawPhone !== '') {
            $request->merge(['phone' => $this->normalizeBdPhone($rawPhone)]);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => [
                'sometimes', 'nullable', 'string',
                'regex:/^8801[3-9]\d{8}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:5000000'],
        ]);

        $updates = collect($data)->only(['name', 'phone'])->all();

        // Changing the phone invalidates any prior OTP verification — the new
        // number hasn't been proven yet, even if the old one was.
        if (array_key_exists('phone', $updates) && $updates['phone'] !== $user->phone) {
            $updates['phone_verified_at'] = null;
        }

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
            'verification_method' => $user->verification_method,
            'email_verified' => $user->hasVerifiedEmail(),
            'phone_verified' => $user->hasVerifiedPhone(),
            'is_active' => (bool) $user->is_active,
            'web_access' => (bool) $user->web_access,
            'package' => $user->package,
            'subscription_status' => $user->subscription_status,
            'subscription_expires_at' => $user->subscription_expires_at?->toIso8601String(),
            'has_active_subscription' => $user->hasActiveSubscription(),
            'backup_frequency' => $user->backup_frequency,
            'last_backup_at' => $user->last_backup_at?->toIso8601String(),
            // Set once, the first time this account's ledger was handed off to a
            // device (see MigrationController::confirm). The server no longer
            // holds this user's ledger after that — a fresh device must restore
            // from the Drive-backed vault backup instead of expecting a migration.
            'migrated_at' => $user->migrated_at?->toIso8601String(),
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
