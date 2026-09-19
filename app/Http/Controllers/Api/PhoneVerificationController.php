<?php

namespace App\Http\Controllers\Api;

use App\Services\PhoneOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends ApiController
{
    public function __construct(private readonly PhoneOtpService $otp) {}

    /**
     * Send (or resend) a verification OTP to the authenticated user's phone.
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return $this->ok(['phone_verified' => true], 'Phone already verified.');
        }

        $sent = $this->otp->send($user);

        return $this->ok(
            ['phone_verified' => false],
            $sent ? 'Verification code sent.' : 'Could not send the verification code. Please try again shortly.',
        );
    }

    /**
     * Verify the OTP the user typed in.
     */
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return $this->ok(['phone_verified' => true], 'Phone already verified.');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $this->otp->verify($user, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['That code is invalid or expired.'],
            ]);
        }

        $user->grantFreeTrialIfEligible(); // phone just became the verified channel → start the trial

        return $this->ok(['phone_verified' => true], 'Phone verified.');
    }

    /**
     * Report whether the authenticated user's phone is verified (the app
     * polls this after the user says they entered the code).
     */
    public function status(Request $request): JsonResponse
    {
        return $this->ok(['phone_verified' => $request->user()->hasVerifiedPhone()]);
    }
}
