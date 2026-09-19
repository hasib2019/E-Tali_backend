<?php

namespace App\Http\Controllers\Api;

use App\Models\PhoneOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives Qolek's "Delivery Status Callback" so we can see whether an SMS
 * actually reached the handset, not just that our send request was accepted.
 *
 * Qolek's exact callback payload isn't in the docs we have, so every call is
 * logged in full — check storage/logs/laravel.log after the first real
 * callback lands and adjust the field names below if they don't match.
 */
class SmsWebhookController extends ApiController
{
    public function qolek(Request $request, string $token): JsonResponse
    {
        $expected = (string) config('services.qolek.webhook_secret');
        abort_unless($expected !== '' && hash_equals($expected, $token), 403);

        $payload = $request->all();
        Log::info('Qolek delivery callback received.', $payload);

        $messageId = $payload['message_id'] ?? $payload['messageId'] ?? $payload['id'] ?? null;
        $status = $payload['status'] ?? $payload['delivery_status'] ?? $payload['deliveryStatus'] ?? null;

        if ($messageId) {
            $otp = PhoneOtp::where('message_id', $messageId)->first();

            if ($otp) {
                $delivered = in_array(strtolower((string) $status), ['delivered', 'success'], true);
                $otp->update([
                    'delivery_status' => $status,
                    'delivered_at' => $delivered ? now() : $otp->delivered_at,
                ]);
            } else {
                Log::warning('Qolek delivery callback: no matching OTP for message_id.', ['message_id' => $messageId]);
            }
        }

        return $this->ok(null, 'ok');
    }
}
