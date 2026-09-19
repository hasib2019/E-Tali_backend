<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the Qolek SMS gateway (https://sms.qolek.com).
 *
 * Sandbox vs Live is controlled on Qolek's dashboard for the configured
 * API key, not by anything in this class — the same endpoint + key are used
 * either way, so local/testing environments should use a key left in
 * Sandbox mode rather than branching in code.
 */
class QolekSmsService
{
    private string $baseUrl;

    private ?string $apiKey;

    private ?string $apiSecret;

    private ?string $senderId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.qolek.base_url', 'https://sms.qolek.com/api/v1'), '/');
        $this->apiKey = config('services.qolek.api_key');
        $this->apiSecret = config('services.qolek.api_secret');
        $this->senderId = config('services.qolek.sender_id');
    }

    /**
     * Send an SMS. Returns ['ok' => bool, 'message_id' => ?string] — the
     * message_id (Qolek's own id for the send, e.g. "SANDBOX_A9X7K2M1Q") is
     * what the delivery-status webhook reports back against, so callers that
     * want delivery tracking should store it.
     *
     * Never throws — a gateway hiccup must not fail the caller's request;
     * failures are logged so they're still visible.
     *
     * @return array{ok: bool, message_id: ?string}
     */
    public function send(string $phone, string $message): array
    {
        if (! $this->apiKey || ! $this->apiSecret) {
            Log::warning('Qolek SMS: QOLEK_API_KEY / QOLEK_API_SECRET is not configured, skipping send.', ['phone' => $phone]);

            return ['ok' => false, 'message_id' => null];
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-SECRET' => $this->apiSecret,
            ])
                ->timeout(10)
                ->post("{$this->baseUrl}/sms/send", [
                    'recipient' => $phone,
                    'sender_id' => $this->senderId,
                    'message' => $message,
                ]);

            if (! $response->successful() || $response->json('status') !== 'success') {
                Log::warning('Qolek SMS send failed.', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['ok' => false, 'message_id' => null];
            }

            return ['ok' => true, 'message_id' => $response->json('data.message_id')];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message_id' => null];
        }
    }
}
