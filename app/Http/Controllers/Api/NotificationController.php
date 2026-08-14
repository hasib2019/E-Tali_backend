<?php

namespace App\Http\Controllers\Api;

use App\Models\PushMessage;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /** The current user's in-app notification inbox (latest first). */
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->notificationsInbox()
            ->latest()
            ->limit(100)
            ->get();

        return $this->ok($items);
    }

    /** Unread badge count. */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->notificationsInbox()->whereNull('read_at')->count();

        return $this->ok(['count' => $count]);
    }

    public function markRead(Request $request, UserNotification $userNotification): JsonResponse
    {
        $this->ensureOwns($request, $userNotification);
        $userNotification->update(['read_at' => $userNotification->read_at ?? now()]);

        return $this->ok($userNotification);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notificationsInbox()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->ok(null, 'All marked read.');
    }

    /** Called when a push is tapped — marks read + records the open (once). */
    public function opened(Request $request, UserNotification $userNotification): JsonResponse
    {
        $this->ensureOwns($request, $userNotification);

        $firstOpen = $userNotification->opened_at === null;
        $userNotification->update([
            'read_at' => $userNotification->read_at ?? now(),
            'opened_at' => $userNotification->opened_at ?? now(),
        ]);

        if ($firstOpen && $userNotification->push_message_id) {
            PushMessage::whereKey($userNotification->push_message_id)->increment('opened_count');
        }

        return $this->ok($userNotification);
    }

    private function ensureOwns(Request $request, UserNotification $userNotification): void
    {
        abort_unless($userNotification->user_id === $request->user()->id, 403, 'Not your notification.');
    }
}
