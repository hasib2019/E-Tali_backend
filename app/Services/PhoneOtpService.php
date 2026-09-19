<?php

namespace App\Services;

use App\Models\User;
use App\Services\Sms\QolekSmsService;
use Illuminate\Support\Facades\Hash;

class PhoneOtpService
{
    private const PURPOSE = 'phone_verification';

    private const TTL_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly QolekSmsService $sms) {}

    /**
     * Generate a fresh 6-digit OTP, invalidate any earlier unconsumed code
     * for this user, and text it to their registered phone number.
     */
    public function send(User $user): bool
    {
        $code = (string) random_int(100000, 999999);

        $user->phoneOtps()
            ->where('purpose', self::PURPOSE)
            ->whereNull('consumed_at')
            ->delete();

        $otp = $user->phoneOtps()->create([
            'phone' => $user->phone,
            'code' => Hash::make($code),
            'purpose' => self::PURPOSE,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $result = $this->sms->send(
            $user->phone,
            "Your Tali Khata verification code is {$code}. It expires in ".self::TTL_MINUTES.' minutes.',
        );

        if ($result['message_id']) {
            $otp->update(['message_id' => $result['message_id']]);
        }

        return $result['ok'];
    }

    /**
     * Check a submitted code against the latest unconsumed OTP for the user.
     * Marks the user's phone verified (and consumes the OTP) on success.
     */
    public function verify(User $user, string $code): bool
    {
        $otp = $user->phoneOtps()
            ->where('purpose', self::PURPOSE)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);
        $user->forceFill(['phone_verified_at' => now()])->save();

        return true;
    }
}
