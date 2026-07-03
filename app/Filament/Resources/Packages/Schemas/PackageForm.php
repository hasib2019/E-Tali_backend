<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('৳'),
                TextInput::make('duration_days')
                    ->label('Duration (days)')
                    ->required()
                    ->numeric(),
                TextInput::make('description')
                    ->default(null),
                TextInput::make('max_businesses')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_parties')
                    ->numeric()
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
