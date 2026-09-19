<?php

namespace App\Filament\Resources\PhoneOtps\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PhoneOtpsTable
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
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('purpose')
                    ->badge(),
                TextColumn::make('message_id')
                    ->label('Gateway message id')
                    ->placeholder('— not sent (no API key / gateway error) —')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('delivery_status')
                    ->label('Delivery status')
                    ->badge()
                    ->placeholder('Awaiting callback')
                    ->colors([
                        'success' => fn (?string $state): bool => in_array(strtolower((string) $state), ['delivered', 'success'], true),
                        'danger' => fn (?string $state): bool => $state !== null && ! in_array(strtolower((string) $state), ['delivered', 'success'], true),
                    ]),
                IconColumn::make('consumed_at')
                    ->label('Used')
                    ->boolean(),
                TextColumn::make('attempts')
                    ->label('Wrong tries'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y H:i'),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('purpose')
                    ->options(['phone_verification' => 'Phone verification']),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
