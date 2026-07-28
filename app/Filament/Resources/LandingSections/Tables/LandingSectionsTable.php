<?php

namespace App\Filament\Resources\LandingSections\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class LandingSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Section')
                    ->description(fn ($record): ?string => $record->description)
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('key')
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('is_active')
                    ->label('Visible'),
                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit section'),
            ]);
    }
}
