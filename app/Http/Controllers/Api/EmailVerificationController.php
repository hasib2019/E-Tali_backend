<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends ApiController
{
    /**
     * Verify the email from a signed link (clicked in the browser from the
     * verification email). Renders a small page that deep-links back to the app.
     * The `signed` middleware guarantees the URL is authentic and unexpired.
     */
    public function verify(Request $request, string $id, string $hash): View
    {
        $user = User::findOrFail($id);

        abort_unless(
            hash_equals($hash, sha1($user->getEmailForVerification())),
            403,
            'Invalid verification link.',
        );

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->grantFreeTrialIfEligible(); // start the free trial right away
        }

        return view('email-verified', [
            'appUrl' => config('app.frontend_url'),
        ]);
    }

    /**
     * Resend the verification email to the authenticated (but unverified) user.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->ok(['email_verified' => true], 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return $this->ok(['email_verified' => false], 'Verification email sent.');
    }

    /**
     * Report whether the authenticated user's email is verified (the app polls
     * this after the user says they clicked the link).
     */
    public function status(Request $request): JsonResponse
    {
        return $this->ok(['email_verified' => $request->user()->hasVerifiedEmail()]);
    }
}
