<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'expired',
                        'gray' => 'cancelled',
                    ]),
                TextColumn::make('admin.name')
                    ->label('By admin')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
