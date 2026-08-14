<?php

namespace App\Filament\Resources\PushMessages\Pages;

use App\Filament\Resources\PushMessages\PushMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPushMessages extends ListRecords
{
    protected static string $resource = PushMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
