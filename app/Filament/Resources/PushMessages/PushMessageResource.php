<?php

namespace App\Filament\Resources\PushMessages;

use App\Filament\Resources\PushMessages\Pages\CreatePushMessage;
use App\Filament\Resources\PushMessages\Pages\EditPushMessage;
use App\Filament\Resources\PushMessages\Pages\ListPushMessages;
use App\Filament\Resources\PushMessages\Schemas\PushMessageForm;
use App\Filament\Resources\PushMessages\Tables\PushMessagesTable;
use App\Models\PushMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PushMessageResource extends Resource
{
    protected static ?string $model = PushMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Push Notifications';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PushMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PushMessagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushMessages::route('/'),
            'create' => CreatePushMessage::route('/create'),
            'edit' => EditPushMessage::route('/{record}/edit'),
        ];
    }
}
