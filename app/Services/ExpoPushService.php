<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications through the Expo Push Service.
 * https://docs.expo.dev/push-notifications/sending-notifications/
 */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * Push a notification to the given Expo tokens (chunked at 100/request).
     *
     * @param  list<string>  $tokens
     * @return array{sent: int, failed: int, invalid: list<string>}
     */
    public function send(array $tokens, string $title, string $body, array $data = []): array
    {
        $sent = 0;
        $failed = 0;
        $invalid = [];

        $tokens = array_values(array_unique(array_filter($tokens, [$this, 'looksLikeExpoToken'])));

        foreach (array_chunk($tokens, 100) as $chunk) {
            $messages = array_map(fn (string $to) => [
                'to' => $to,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => 'default',
            ], $chunk);

            try {
                $response = Http::acceptJson()->asJson()->post(self::ENDPOINT, $messages);
            } catch (\Throwable $e) {
                Log::warning('Expo push request failed', ['error' => $e->getMessage()]);
                $failed += count($chunk);

                continue;
            }

            $tickets = $response->json('data') ?? [];
            foreach ($chunk as $i => $token) {
                $ticket = $tickets[$i] ?? null;
                if ($ticket && ($ticket['status'] ?? null) === 'ok') {
                    $sent++;

                    continue;
                }
                $failed++;
                if (($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                    $invalid[] = $token;
                }
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'invalid' => $invalid];
    }

    private function looksLikeExpoToken(string $token): bool
    {
        return str_starts_with($token, 'ExponentPushToken[') || str_starts_with($token, 'ExpoPushToken[');
    }
}
