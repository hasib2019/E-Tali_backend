<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Filament\Widgets\Widget;

/**
 * Which screens users open most, and who was active recently — the "who uses
 * what" view built from analytics_events + users.last_active_at.
 */
class TopPagesWidget extends Widget
{
    protected string $view = 'filament.widgets.top-pages-widget';

    protected int|string|array $columnSpan = 'full';

    /** @return array<int, array{screen: string, views: int, users: int}> */
    public function getTopPages(): array
    {
        return AnalyticsEvent::query()
            ->where('occurred_at', '>=', now()->subDays(30))
            ->selectRaw('screen, COUNT(*) as views, COUNT(DISTINCT user_id) as users')
            ->groupBy('screen')
            ->orderByDesc('views')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'screen' => (string) $r->screen,
                'views' => (int) $r->views,
                'users' => (int) $r->users,
            ])
            ->all();
    }

    /** @return array<int, array{name: string, email: string, screen: ?string, active: ?string}> */
    public function getRecentUsers(): array
    {
        return User::query()
            ->whereNotNull('last_active_at')
            ->orderByDesc('last_active_at')
            ->limit(12)
            ->get(['name', 'email', 'last_screen', 'last_active_at'])
            ->map(fn (User $u) => [
                'name' => $u->name,
                'email' => $u->email,
                'screen' => $u->last_screen,
                'active' => $u->last_active_at?->diffForHumans(),
            ])
            ->all();
    }
}
