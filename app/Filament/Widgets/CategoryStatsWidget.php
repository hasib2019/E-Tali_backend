<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Support\CategoryRegistry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Admin overview: how many khatas exist per category.
 */
class CategoryStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $counts = Business::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $meta = [
            CategoryRegistry::BUSINESS => ['Business khatas', 'heroicon-o-building-storefront', 'success'],
            CategoryRegistry::SALARIED => ['Salaried khatas', 'heroicon-o-briefcase', 'info'],
            CategoryRegistry::STUDENT => ['Student khatas', 'heroicon-o-academic-cap', 'warning'],
            CategoryRegistry::TEACHER => ['Teacher khatas', 'heroicon-o-user-group', 'danger'],
        ];

        $stats = [];
        foreach ($meta as $key => [$label, $icon, $color]) {
            $stats[] = Stat::make($label, (string) ($counts[$key] ?? 0))
                ->icon($icon)
                ->color($color);
        }

        return $stats;
    }
}
