<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Which screens users open most. Grouped by screen, so `MIN(id)` stands in as
 * the row key Filament needs for a table built on an aggregate query.
 */
class TopScreensWidget extends TableWidget
{
    protected static ?string $heading = 'Most-used screens (30 days)';

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AnalyticsEvent::query()
                    ->where('occurred_at', '>=', now()->subDays(30))
                    ->selectRaw('MIN(id) as id, screen, COUNT(*) as views, COUNT(DISTINCT user_id) as users')
                    ->groupBy('screen'),
            )
            ->defaultSort('views', 'desc')
            ->columns([
                TextColumn::make('screen')
                    ->label('Screen')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('users')
                    ->label('Users')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No screen views yet')
            ->emptyStateDescription('Usage shows up here once people start opening screens in the app.')
            ->emptyStateIcon('heroicon-o-chart-bar')
            ->paginated([10, 25, 50]);
    }
}
