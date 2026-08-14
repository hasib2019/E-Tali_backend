<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\PushMessage;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\ExpoPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Resolves a PushMessage's audience, writes an in-app inbox row for every
 * targeted user, and fans the message out to their devices via Expo.
 */
class SendPushMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public int $pushMessageId) {}

    public function handle(ExpoPushService $expo): void
    {
        $message = PushMessage::find($this->pushMessageId);
        if (! $message) {
            return;
        }

        $users = $this->resolveAudience($message->audience ?? ['type' => 'all']);

        // 1) In-app inbox row for each targeted user (so old pushes are readable in-app).
        $now = now();
        $rows = $users->map(fn (User $u) => [
            'user_id' => $u->id,
            'push_message_id' => $message->id,
            'title' => $message->title,
            'body' => $message->body,
            'route' => $message->route,
            'data' => $message->data ? json_encode($message->data) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        foreach (array_chunk($rows, 500) as $chunk) {
            UserNotification::insert($chunk);
        }

        // 2) Push to every registered device of those users.
        $tokens = DeviceToken::whereIn('user_id', $users->pluck('id'))->pluck('token')->all();
        $payload = array_merge($message->data ?? [], [
            'route' => $message->route,
            'push_message_id' => $message->id,
        ]);
        $result = $expo->send($tokens, $message->title, $message->body, $payload);

        // 3) Prune tokens Expo reported as dead.
        if ($result['invalid']) {
            DeviceToken::whereIn('token', $result['invalid'])->delete();
        }

        $message->update([
            'status' => 'sent',
            'recipient_count' => $users->count(),
            'sent_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'sent_at' => now(),
        ]);
    }

    /**
     * Turn an audience descriptor into the set of users to notify.
     * Shape: {type: all|users|subscription|category, values: [...]}.
     *
     * @return Collection<int, User>
     */
    private function resolveAudience(array $audience): Collection
    {
        $type = $audience['type'] ?? 'all';
        $values = $audience['values'] ?? [];
        $query = User::query()->where('is_active', true);

        return match ($type) {
            'users' => $query->whereIn('id', $values)->get(),
            'subscription' => $query->whereIn('subscription_status', $values ?: ['active'])->get(),
            'category' => $query->whereHas('businesses', fn ($b) => $b->whereIn('category', $values))->get(),
            default => $query->get(),
        };
    }
}
