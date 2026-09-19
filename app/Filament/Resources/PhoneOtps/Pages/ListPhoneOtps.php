<?php

namespace App\Filament\Resources\PhoneOtps\Pages;

use App\Filament\Resources\PhoneOtps\PhoneOtpResource;
use Filament\Resources\Pages\ListRecords;

class ListPhoneOtps extends ListRecords
{
    protected static string $resource = PhoneOtpResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
