<?php

namespace App\Filament\Resources\SmsLogs;

use App\Filament\Resources\SmsLogs\Pages\ListSmsLogs;
use App\Filament\Resources\SmsLogs\Tables\SmsLogsTable;
use App\Models\SmsLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmsLogResource extends Resource
{
    protected static ?string $model = SmsLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $navigationLabel = 'SMS log (customers)';

    public static function table(Table $table): Table
    {
        return SmsLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // log — populated by grants and app sends only
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsLogs::route('/'),
        ];
    }
}
