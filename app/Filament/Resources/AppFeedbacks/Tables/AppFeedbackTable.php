<?php

namespace App\Filament\Resources\AppFeedbacks\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppFeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->description(fn ($record) => $record->user?->email)
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Feedback')
                    ->wrap()
                    ->limit(160)
                    ->searchable(),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?int $state) => $state ? str_repeat('★', $state) : '—'),
                TextColumn::make('app_version')
                    ->label('Version')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('platform')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'new',
                        'info' => 'read',
                        'success' => 'resolved',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'read' => 'Read',
                        'resolved' => 'Resolved',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Open'),
            ])
            ->toolbarActions([]);
    }
}
