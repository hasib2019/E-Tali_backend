<?php

namespace App\Filament\Resources\DonationTiers\Pages;

use App\Filament\Resources\DonationTiers\DonationTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDonationTiers extends ListRecords
{
    protected static string $resource = DonationTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
