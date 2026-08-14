<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use App\Models\DeviceToken;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Admin engagement snapshot: how many users are active, how many devices can
 * receive pushes, and recent screen-view volume.
 */
class EngagementStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activeToday = User::where('last_active_at', '>=', now()->startOfDay())->count();
        $active7 = User::where('last_active_at', '>=', now()->subDays(7))->count();
        $active30 = User::where('last_active_at', '>=', now()->subDays(30))->count();
        $devices = DeviceToken::count();
        $views7 = AnalyticsEvent::where('occurred_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Active today', (string) $activeToday)
                ->icon('heroicon-o-bolt')->color('success'),
            Stat::make('Active (7 days)', (string) $active7)
                ->icon('heroicon-o-users')->color('info'),
            Stat::make('Active (30 days)', (string) $active30)
                ->icon('heroicon-o-calendar-days')->color('warning'),
            Stat::make('Push devices', (string) $devices)
                ->icon('heroicon-o-device-phone-mobile'),
            Stat::make('Screen views (7 days)', number_format($views7))
                ->icon('heroicon-o-eye'),
        ];
    }
}
