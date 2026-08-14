<?php

namespace App\Filament\Resources\PushMessages\Schemas;

use App\Models\User;
use App\Support\CategoryRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PushMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(120),

                Textarea::make('body')
                    ->label('Message')
                    ->required()
                    ->rows(3)
                    ->maxLength(500),

                TextInput::make('route')
                    ->label('Open screen on tap (optional)')
                    ->placeholder('/subscription')
                    ->helperText('App route opened when the user taps the notification — e.g. /report, /subscription, /notifications.'),

                Select::make('audience_type')
                    ->label('Send to')
                    ->options([
                        'all' => 'All users',
                        'category' => 'By khata category',
                        'subscription' => 'By subscription status',
                        'users' => 'Specific users',
                    ])
                    ->default('all')
                    ->required()
                    ->live(),

                Select::make('audience_values')
                    ->label('Choose')
                    ->multiple()
                    ->searchable()
                    ->options(fn (Get $get): array => match ($get('audience_type')) {
                        'category' => collect(CategoryRegistry::CATEGORIES)
                            ->mapWithKeys(fn (string $c) => [$c => ucfirst($c)])
                            ->all(),
                        'subscription' => ['none' => 'No subscription', 'active' => 'Active', 'expired' => 'Expired'],
                        'users' => User::orderBy('name')->pluck('name', 'id')->all(),
                        default => [],
                    })
                    ->visible(fn (Get $get): bool => in_array($get('audience_type'), ['category', 'subscription', 'users'], true))
                    ->required(fn (Get $get): bool => in_array($get('audience_type'), ['category', 'subscription', 'users'], true)),
            ]);
    }
}
