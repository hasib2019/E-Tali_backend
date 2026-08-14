<?php

namespace App\Filament\Resources\PushMessages\Pages;

use App\Filament\Resources\PushMessages\PushMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePushMessage extends CreateRecord
{
    protected static string $resource = PushMessageResource::class;

    /** Fold the two audience controls into the JSON `audience` column. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['audience'] = [
            'type' => $data['audience_type'] ?? 'all',
            'values' => $data['audience_values'] ?? [],
        ];
        $data['status'] = 'draft';
        unset($data['audience_type'], $data['audience_values']);

        return $data;
    }
}
