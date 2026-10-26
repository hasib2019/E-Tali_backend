<?php

namespace App\Filament\Resources\SmsLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SmsLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->colors(['info' => 'grant', 'gray' => 'send']),
                TextColumn::make('credits')
                    ->label('Credits')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                TextColumn::make('status')
                    ->badge()
                    ->colors(['success' => 'sent', 'danger' => 'failed', 'info' => 'granted']),
                TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('party_name')
                    ->label('Customer')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('business_name')
                    ->label('Business')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('message')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->message)
                    ->placeholder('—'),
                TextColumn::make('parts')
                    ->label('Parts'),
                TextColumn::make('delivery_status')
                    ->label('Delivery')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('note')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('admin.name')
                    ->label('By admin')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options(['grant' => 'Grant', 'send' => 'Send']),
                SelectFilter::make('status')->options(['sent' => 'Sent', 'failed' => 'Failed', 'granted' => 'Granted']),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
