<?php

namespace App\Filament\Resources\Donations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DonationForm
{
    /** Verify a claimed donation against the bKash statement, then flip its status. */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.name')
                    ->label('Donor')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('user.email')
                    ->label('Donor email')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('tier_label')
                    ->label('Tier')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('amount')
                    ->prefix('৳')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('bkash_number')
                    ->label('bKash number used')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('trx_id')
                    ->label('bKash transaction ID')
                    ->maxLength(40),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ]);
    }
}
