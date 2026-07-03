<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Turning this off immediately locks the user out of the app.')
                    ->default(true),
                TextInput::make('provider')
                    ->disabled()
                    ->dehydrated(false),
                DateTimePicker::make('email_verified_at')
                    ->label('Email verified at')
                    ->helperText('Set a date to mark the email as verified; clear to require verification.'),
                TextInput::make('password')
                    ->label('Set new password')
                    ->password()
                    ->revealable()
                    ->helperText('Leave blank to keep the current password.')
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                // --- Subscription (usually managed via the "Assign / Renew" action) ---
                Select::make('package_id')
                    ->label('Package')
                    ->relationship('package', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('subscription_status')
                    ->options([
                        'none' => 'None',
                        'active' => 'Active',
                        'expired' => 'Expired',
                    ])
                    ->default('none')
                    ->required(),
                DateTimePicker::make('subscribed_at'),
                DateTimePicker::make('subscription_expires_at')
                    ->label('Subscription expires at'),
                Toggle::make('is_paid')
                    ->label('Paid'),

                // --- Backup (read-only; the user controls this from the app) ---
                TextInput::make('backup_frequency')
                    ->disabled()
                    ->dehydrated(false),
                DateTimePicker::make('last_backup_at')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
