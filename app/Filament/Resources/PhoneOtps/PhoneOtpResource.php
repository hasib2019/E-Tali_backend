<?php

namespace App\Filament\Resources\PhoneOtps;

use App\Filament\Resources\PhoneOtps\Pages\ListPhoneOtps;
use App\Filament\Resources\PhoneOtps\Tables\PhoneOtpsTable;
use App\Models\PhoneOtp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PhoneOtpResource extends Resource
{
    protected static ?string $model = PhoneOtp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'SMS OTP log';

    public static function table(Table $table): Table
    {
        return PhoneOtpsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // log — populated by registration / phone verification only
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhoneOtps::route('/'),
        ];
    }
}
