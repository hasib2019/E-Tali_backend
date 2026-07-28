<?php

namespace App\Filament\Resources\Admins\Schemas;

use App\Models\Admin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Admin account')
                    ->description('Create an admin or control their role and panel access.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('role')
                            ->options([
                                Admin::ROLE_SUPER_ADMIN => 'Super Admin',
                                Admin::ROLE_ADMIN => 'Admin',
                            ])
                            ->default(Admin::ROLE_ADMIN)
                            ->required()
                            ->disabled(fn (?Admin $record): bool => $record?->getKey() === auth('admin')->id())
                            ->helperText('Super Admin can add and manage other admin accounts.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->disabled(fn (?Admin $record): bool => $record?->getKey() === auth('admin')->id())
                            ->helperText('Inactive accounts cannot sign in to the admin panel.'),
                    ]),
                Section::make('Password')
                    ->description('Leave these fields empty while editing to keep the current password.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
