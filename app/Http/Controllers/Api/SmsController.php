<?php

namespace App\Http\Controllers\Api;

use App\Models\SmsLog;
use App\Models\SmsSetting;
use App\Services\Sms\SmsCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer SMS from the app (tagada, transaction alerts). The phone numbers
 * and amounts live on the device, so the app composes each message and the
 * server only checks credits, sends, and logs.
 */
class SmsController extends ApiController
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $recent = SmsLog::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(30)
            ->get(['id', 'type', 'credits', 'phone', 'message', 'parts', 'status', 'delivery_status', 'party_name', 'note', 'created_at']);

        return $this->ok([
            'enabled' => SmsSetting::current()->enabled,
            'balance' => (int) $user->sms_balance,
            'sent_total' => (int) $user->sms_sent_total,
            'max_parts' => SmsCreditService::MAX_PARTS_PER_MESSAGE,
            'recent' => $recent,
        ]);
    }

    public function send(Request $request, SmsCreditService $sms): JsonResponse
    {
        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:190'],
            'messages' => ['required', 'array', 'min:1', 'max:50'],
            'messages.*.phone' => ['required', 'string', 'max:20'],
            'messages.*.message' => ['required', 'string', 'max:1000'],
            'messages.*.party_name' => ['nullable', 'string', 'max:190'],
        ]);

        $user = $request->user();
        $results = [];
        foreach ($data['messages'] as $i => $item) {
            $results[] = ['index' => $i] + $sms->send($user, $item['phone'], $item['message'], [
                'business_name' => $data['business_name'] ?? null,
                'party_name' => $item['party_name'] ?? null,
            ]);
        }

        $sent = count(array_filter($results, fn ($r) => $r['ok']));

        return $this->ok([
            'sent' => $sent,
            'failed' => count($results) - $sent,
            'balance' => (int) $user->fresh()->sms_balance,
            'results' => $results,
        ], $sent > 0 ? 'SMS sent' : 'SMS not sent');
    }
}
