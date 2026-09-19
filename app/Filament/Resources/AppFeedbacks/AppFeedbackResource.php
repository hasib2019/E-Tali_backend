<?php

namespace App\Filament\Resources\AppFeedbacks;

use App\Filament\Resources\AppFeedbacks\Pages\ListAppFeedback;
use App\Filament\Resources\AppFeedbacks\Schemas\AppFeedbackForm;
use App\Filament\Resources\AppFeedbacks\Tables\AppFeedbackTable;
use App\Models\AppFeedback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppFeedbackResource extends Resource
{
    protected static ?string $model = AppFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'App feedback';

    protected static ?string $modelLabel = 'feedback';

    protected static ?string $pluralModelLabel = 'app feedback';

    public static function form(Schema $schema): Schema
    {
        return AppFeedbackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppFeedbackTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // written by users from the app only
    }

    /** Unread count on the sidebar so new feedback is noticed. */
    public static function getNavigationBadge(): ?string
    {
        $new = AppFeedback::where('status', 'new')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppFeedback::route('/'),
        ];
    }
}
