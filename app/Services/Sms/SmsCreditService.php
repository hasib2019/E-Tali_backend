<?php

namespace App\Services\Sms;

use App\Models\SmsLog;
use App\Models\SmsSetting;
use App\Models\User;
use App\Support\SmsSegments;
use Illuminate\Support\Facades\DB;

/**
 * The only place SMS credits move. Grants come from the admin panel (one
 * user, a selection, or everyone) and from package assignment; spending comes
 * from the app's tagada / alert sends. Every movement writes an `sms_logs` row.
 */
class SmsCreditService
{
    /** Longest message we accept, in billable parts (5 Bangla parts ≈ 335 chars). */
    public const MAX_PARTS_PER_MESSAGE = 5;

    public function __construct(private QolekSmsService $gateway) {}

    /**
     * Add (or, with a negative amount, take back) credits. The balance never
     * goes below zero.
     */
    public function grant(User $user, int $credits, ?string $note = null, ?int $adminId = null): void
    {
        if ($credits === 0) {
            return;
        }

        DB::transaction(function () use ($user, $credits, $note, $adminId) {
            $fresh = User::query()->lockForUpdate()->findOrFail($user->id);
            $applied = max(-$fresh->sms_balance, $credits);
            $fresh->forceFill(['sms_balance' => $fresh->sms_balance + $applied])->save();

            SmsLog::create([
                'user_id' => $user->id,
                'type' => SmsLog::TYPE_GRANT,
                'credits' => $applied,
                'status' => 'granted',
                'note' => $note,
                'admin_id' => $adminId,
            ]);
        });

        $user->refresh();
    }

    /** Same amount to many users in one go; returns how many were credited. */
    public function grantMany(iterable $users, int $credits, ?string $note = null, ?int $adminId = null): int
    {
        $count = 0;
        foreach ($users as $user) {
            $this->grant($user, $credits, $note, $adminId);
            $count++;
        }

        return $count;
    }

    /**
     * Send one SMS on the user's behalf. Credits are reserved before calling
     * the gateway and refunded if the gateway refuses, so a failed send is free.
     *
     * @param  array{business_name?: ?string, party_name?: ?string}  $context
     * @return array{ok: bool, error: ?string, parts: int, log_id: ?int}
     */
    public function send(User $user, string $phone, string $message, array $context = []): array
    {
        $message = trim($message);
        $parts = SmsSegments::count($message);

        if (! SmsSetting::current()->enabled) {
            return ['ok' => false, 'error' => 'disabled', 'parts' => $parts, 'log_id' => null];
        }
        if ($parts === 0 || $parts > self::MAX_PARTS_PER_MESSAGE) {
            return ['ok' => false, 'error' => 'too_long', 'parts' => $parts, 'log_id' => null];
        }

        $normalized = User::normalizeBdPhone($phone);
        if (! preg_match('/^8801[3-9]\d{8}$/', $normalized)) {
            return ['ok' => false, 'error' => 'invalid_phone', 'parts' => $parts, 'log_id' => null];
        }

        // Atomic reserve: only succeeds while the balance covers the parts.
        $reserved = DB::table('users')
            ->where('id', $user->id)
            ->where('sms_balance', '>=', $parts)
            ->decrement('sms_balance', $parts);
        if ($reserved === 0) {
            return ['ok' => false, 'error' => 'insufficient_balance', 'parts' => $parts, 'log_id' => null];
        }

        $result = $this->gateway->send($normalized, $message);

        if (! $result['ok']) {
            DB::table('users')->where('id', $user->id)->increment('sms_balance', $parts);
        } else {
            DB::table('users')->where('id', $user->id)->increment('sms_sent_total', $parts);
        }

        $log = SmsLog::create([
            'user_id' => $user->id,
            'type' => SmsLog::TYPE_SEND,
            'credits' => $result['ok'] ? -$parts : 0,
            'phone' => $normalized,
            'message' => $message,
            'parts' => $parts,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'message_id' => $result['message_id'],
            'business_name' => isset($context['business_name']) ? mb_substr((string) $context['business_name'], 0, 190) : null,
            'party_name' => isset($context['party_name']) ? mb_substr((string) $context['party_name'], 0, 190) : null,
        ]);

        $user->refresh();

        return [
            'ok' => $result['ok'],
            'error' => $result['ok'] ? null : 'gateway_failed',
            'parts' => $parts,
            'log_id' => $log->id,
        ];
    }
}
