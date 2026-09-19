<?php

namespace App\Filament\Resources\AppFeedbacks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppFeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->label('What the user said')
                    ->rows(6)
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->options([
                        'new' => 'New',
                        'read' => 'Read',
                        'resolved' => 'Resolved',
                    ])
                    ->required(),
                Textarea::make('admin_note')
                    ->label('Internal note')
                    ->rows(3),
            ]);
    }
}
