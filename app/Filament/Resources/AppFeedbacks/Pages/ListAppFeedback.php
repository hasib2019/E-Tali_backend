<?php

namespace App\Filament\Resources\AppFeedbacks\Pages;

use App\Filament\Resources\AppFeedbacks\AppFeedbackResource;
use Filament\Resources\Pages\ListRecords;

class ListAppFeedback extends ListRecords
{
    protected static string $resource = AppFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
