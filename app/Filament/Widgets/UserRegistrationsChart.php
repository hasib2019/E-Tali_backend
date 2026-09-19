<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * How many users signed up over time, so the totals are readable at a glance.
 * Days are bucketed in PHP from a single DATE()-grouped query — DATE() behaves
 * the same on MySQL and SQLite, unlike DATE_FORMAT/strftime.
 */
class UserRegistrationsChart extends ChartWidget
{
    protected ?string $heading = 'User registrations';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    protected function getType(): string
    {
        return 'bar';
    }

    public function getDescription(): ?string
    {
        $total = User::count();
        $thisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();
        $today = User::where('created_at', '>=', now()->startOfDay())->count();

        return "Total {$total} · this month {$thisMonth} · today {$today}";
    }

    /** @return array<string, string> */
    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            '365' => 'Last 12 months',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 30);
        $byMonth = $days > 90;
        $start = now()->startOfDay()->subDays($days - 1);

        $perDay = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $counts = [];
        foreach ($perDay as $date => $total) {
            $key = $byMonth ? Carbon::parse($date)->format('Y-m') : (string) $date;
            $counts[$key] = ($counts[$key] ?? 0) + (int) $total;
        }

        // Walk the whole window so empty days/months still show as zero.
        $labels = [];
        $values = [];
        if ($byMonth) {
            $cursor = $start->copy()->startOfMonth();
            $end = now()->startOfMonth();
            while ($cursor <= $end) {
                $labels[] = $cursor->format('M Y');
                $values[] = $counts[$cursor->format('Y-m')] ?? 0;
                $cursor->addMonth();
            }
        } else {
            $cursor = $start->copy();
            $end = now()->startOfDay();
            while ($cursor <= $end) {
                $labels[] = $cursor->format('d M');
                $values[] = $counts[$cursor->format('Y-m-d')] ?? 0;
                $cursor->addDay();
            }
        }

        return [
            'datasets' => [[
                'label' => 'New users',
                'data' => $values,
                'backgroundColor' => 'rgba(16, 185, 129, 0.55)',
                'borderColor' => '#0E9F6E',
                'borderWidth' => 1,
            ]],
            'labels' => $labels,
        ];
    }
}
