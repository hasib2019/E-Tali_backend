<?php

namespace App\Filament\Resources\DonationTiers\Pages;

use App\Filament\Resources\DonationTiers\DonationTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDonationTier extends EditRecord
{
    protected static string $resource = DonationTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
