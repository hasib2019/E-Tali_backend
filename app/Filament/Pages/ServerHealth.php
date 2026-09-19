<?php

namespace App\Filament\Pages;

use App\Services\ServerHealthService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Live server health: database pressure, who is using the app right now, and
 * the host's vitals. Refreshes itself every 10s.
 */
class ServerHealth extends Page
{
    protected string $view = 'filament.pages.server-health';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Health';

    protected static ?string $title = 'Server Health';

    protected static ?int $navigationSort = 90;

    /** Seconds between auto-refreshes; `null` pauses it. */
    public ?string $pollInterval = '10s';

    public function getPollInterval(): ?string
    {
        return $this->pollInterval;
    }

    public function togglePolling(): void
    {
        $this->pollInterval = $this->pollInterval === null ? '10s' : null;
    }

    /** @return array<string, mixed> */
    public function getHealth(): array
    {
        $service = app(ServerHealthService::class);

        return [
            'database' => $service->database(),
            'tables' => $service->largestTables(),
            'users' => $service->users(),
            'activity' => $service->activity(),
            'top_screens' => $service->topScreens(),
            'recent_views' => $service->recentViews(),
            'system' => $service->system(),
            'jobs' => $service->jobs(),
            'checked_at' => now()->format('H:i:s'),
        ];
    }
}
