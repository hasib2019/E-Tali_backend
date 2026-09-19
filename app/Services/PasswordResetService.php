<?php

namespace App\Services;

use App\Models\PasswordResetCode;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use App\Services\Sms\QolekSmsService;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    private const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly QolekSmsService $sms) {}

    /**
     * Find the account behind whatever the user typed — their email or their
     * phone number (in any of the shapes we accept for a BD mobile).
     */
    public function findUser(string $identifier): ?User
    {
        return User::findByIdentifier($identifier);
    }

    /**
     * Issue a fresh code and deliver it. `$channel` falls back to whichever
     * channel the account can actually receive on.
     */
    public function send(User $user, string $channel): bool
    {
        $channel = $channel === 'phone' && $user->phone ? 'phone' : 'email';
        $destination = $channel === 'phone' ? (string) $user->phone : (string) $user->email;
        $code = (string) random_int(100000, 999999);

        $user->passwordResetCodes()->whereNull('consumed_at')->delete();
        $user->passwordResetCodes()->create([
            'channel' => $channel,
            'destination' => $destination,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        if ($channel === 'phone') {
            return $this->sms->send(
                $destination,
                "Your Tali Khata password reset code is {$code}. It expires in ".self::TTL_MINUTES.' minutes.',
            )['ok'];
        }

        $user->notify(new PasswordResetCodeNotification($code, self::TTL_MINUTES));

        return true;
    }

    /**
     * Check the submitted code and, when it matches, set the new password and
     * sign every existing session out.
     */
    public function reset(User $user, string $code, string $password): bool
    {
        $entry = $user->passwordResetCodes()
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $entry || $entry->expires_at->isPast() || $entry->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $entry->code)) {
            $entry->increment('attempts');

            return false;
        }

        $entry->update(['consumed_at' => now()]);
        $user->update(['password' => $password]);
        // A reset is also the remedy for a stolen session — drop every token.
        $user->tokens()->delete();

        return true;
    }
}
