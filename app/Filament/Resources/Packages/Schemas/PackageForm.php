<?php

namespace App\Filament\Resources\Packages\Schemas;

use App\Support\CategoryRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PackageForm
{
    /** App feature flags the owner can toggle per package. */
    public const FEATURES = [
        'backup' => 'Cloud backup (Drive + hosting)',
        'auto_backup' => 'Automatic scheduled backup',
        'products' => 'Products & inventory',
        'reports' => 'Reports & statements',
        'tagada' => 'Group reminder (tagada)',
        'notes' => 'Business notes',
    ];

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
                    ->label('Max businesses (blank = unlimited)')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_parties')
                    ->label('Max parties per business (blank = unlimited)')
                    ->numeric()
                    ->default(null),

                CheckboxList::make('allowed_categories')
                    ->label('Allowed khata categories (none selected = ALL allowed)')
                    ->options(collect(CategoryRegistry::CATEGORIES)->mapWithKeys(fn ($c) => [$c => ucfirst($c)])->all())
                    ->columns(3),

                CheckboxList::make('features')
                    ->label('Unlocked features')
                    ->options(self::FEATURES)
                    ->columns(2),

                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
