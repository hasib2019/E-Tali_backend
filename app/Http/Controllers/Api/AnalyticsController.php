<?php

namespace App\Http\Controllers\Api;

use App\Models\AnalyticsEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends ApiController
{
    /**
     * Ingest a batch of screen-view / action events from the app.
     * Also refreshes the user's last-active snapshot for quick "who's online".
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'max:100'],
            'events.*.screen' => ['required', 'string', 'max:120'],
            'events.*.event_type' => ['nullable', 'string', 'max:30'],
            'events.*.business_id' => ['nullable', 'integer'],
            'events.*.occurred_at' => ['nullable', 'date'],
            'platform' => ['nullable', 'string', 'max:20'],
            'app_version' => ['nullable', 'string', 'max:20'],
        ]);

        $userId = $request->user()->id;
        $now = now();

        $rows = [];
        foreach ($data['events'] as $event) {
            $rows[] = [
                'user_id' => $userId,
                'business_id' => $event['business_id'] ?? null,
                'screen' => $event['screen'],
                'event_type' => $event['event_type'] ?? 'screen_view',
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'occurred_at' => isset($event['occurred_at']) ? Carbon::parse($event['occurred_at']) : $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        AnalyticsEvent::insert($rows);

        $request->user()->forceFill([
            'last_active_at' => $now,
            'last_screen' => end($data['events'])['screen'] ?? null,
        ])->saveQuietly();

        return $this->ok(null, 'ok');
    }
}
