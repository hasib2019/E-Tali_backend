<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\Tables\SubscriptionsTable;
use App\Models\Subscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Subscription log';

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // audit log — populated by the "Assign / Renew" action only
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
        ];
    }
}
