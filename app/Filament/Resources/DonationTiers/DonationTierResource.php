<?php

namespace App\Filament\Resources\DonationTiers;

use App\Filament\Resources\DonationTiers\Pages\CreateDonationTier;
use App\Filament\Resources\DonationTiers\Pages\EditDonationTier;
use App\Filament\Resources\DonationTiers\Pages\ListDonationTiers;
use App\Filament\Resources\DonationTiers\Schemas\DonationTierForm;
use App\Filament\Resources\DonationTiers\Tables\DonationTiersTable;
use App\Models\DonationTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DonationTierResource extends Resource
{
    protected static ?string $model = DonationTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $navigationLabel = 'Donation Tiers';

    public static function form(Schema $schema): Schema
    {
        return DonationTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DonationTiersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDonationTiers::route('/'),
            'create' => CreateDonationTier::route('/create'),
            'edit' => EditDonationTier::route('/{record}/edit'),
        ];
    }
}
