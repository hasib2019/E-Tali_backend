<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Who is using the app right now, and what they were last looking at.
 */
class RecentlyActiveUsersWidget extends TableWidget
{
    protected static ?string $heading = 'Recently active users';

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->whereNotNull('last_active_at'))
            ->defaultSort('last_active_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('User')
                    ->description(fn (User $record) => $record->email)
                    ->weight('medium')
                    ->searchable(['name', 'email']),
                TextColumn::make('last_screen')
                    ->label('Last screen')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('last_active_at')
                    ->label('Active')
                    // Relative time reads faster here; the exact stamp is on hover.
                    ->since()
                    ->tooltip(fn (User $record) => $record->last_active_at?->format('d M Y H:i'))
                    ->sortable()
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No activity yet')
            ->emptyStateDescription('Users appear here as soon as they open the app.')
            ->emptyStateIcon('heroicon-o-users')
            ->paginated([10, 25, 50]);
    }
}
