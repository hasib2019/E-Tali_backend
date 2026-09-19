<?php

namespace App\Http\Controllers\Api;

use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * "Forgot password" for the app: the user identifies their account by email or
 * phone, receives a 6-digit code on that channel, and exchanges it for a new
 * password.
 *
 * Responses never reveal whether an account exists — a wrong email would
 * otherwise turn this endpoint into a user directory.
 */
class PasswordResetController extends ApiController
{
    public function __construct(private readonly PasswordResetService $service) {}

    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:email,phone'],
        ]);

        $user = $this->service->findUser($data['identifier']);

        if ($user && $user->is_active) {
            try {
                $this->service->send($user, $data['channel']);
            } catch (\Throwable $e) {
                // A mail/SMS hiccup must not leak "this account exists" either.
                report($e);
            }
        }

        return $this->ok(
            ['channel' => $data['channel']],
            'If that account exists, we have sent a reset code to it.',
        );
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $this->service->findUser($data['identifier']);

        if (! $user || ! $this->service->reset($user, $data['code'], $data['password'])) {
            throw ValidationException::withMessages([
                'code' => ['That code is invalid or expired.'],
            ]);
        }

        return $this->ok(null, 'Password updated. Please log in with your new password.');
    }
}
