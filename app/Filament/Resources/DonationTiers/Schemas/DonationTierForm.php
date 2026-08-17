<?php

namespace App\Filament\Resources\DonationTiers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DonationTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Key (internal, unique)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(40),
                TextInput::make('label')
                    ->required()
                    ->maxLength(60),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('৳'),
                TextInput::make('icon')
                    ->label('Icon (Ionicons outline name)')
                    ->helperText('e.g. cafe-outline, ice-cream-outline, fast-food-outline, pizza-outline, shirt-outline, bag-outline')
                    ->required()
                    ->default('cafe-outline')
                    ->maxLength(60),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
