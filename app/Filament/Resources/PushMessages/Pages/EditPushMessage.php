<?php

namespace App\Filament\Resources\PushMessages\Pages;

use App\Filament\Resources\PushMessages\PushMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPushMessage extends EditRecord
{
    protected static string $resource = PushMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** Split the stored JSON audience back into the two form controls. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['audience_type'] = $data['audience']['type'] ?? 'all';
        $data['audience_values'] = $data['audience']['values'] ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['audience'] = [
            'type' => $data['audience_type'] ?? 'all',
            'values' => $data['audience_values'] ?? [],
        ];
        unset($data['audience_type'], $data['audience_values']);

        return $data;
    }
}
