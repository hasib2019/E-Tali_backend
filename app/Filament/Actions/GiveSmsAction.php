<?php

namespace App\Filament\Actions;

use App\Models\User;
use App\Services\Sms\SmsCreditService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * "Give SMS" in three flavours — one user (row action), the selected users
 * (bulk action) and every user (list header action). A negative amount takes
 * credits back; the balance never drops below zero.
 */
class GiveSmsAction
{
    /** @return array<int, TextInput> */
    protected static function schema(): array
    {
        return [
            TextInput::make('credits')
                ->label('SMS credits')
                ->helperText('Added to the current balance. Use a negative number to take credits back. 1 credit = 1 SMS part (a Bangla SMS longer than 70 characters uses 2+ parts).')
                ->integer()
                ->required()
                ->default(5)
                ->minValue(-100000)
                ->maxValue(100000),
            TextInput::make('note')
                ->label('Note (optional)')
                ->maxLength(190),
        ];
    }

    protected static function notify(int $users, int $credits): void
    {
        Notification::make()
            ->success()
            ->title('SMS credits updated')
            ->body(($credits >= 0 ? "Gave {$credits}" : 'Took back '.abs($credits))." SMS credits to {$users} user(s).")
            ->send();
    }

    public static function forRecord(): Action
    {
        return Action::make('giveSms')
            ->label('Give SMS')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('info')
            ->schema(self::schema())
            ->action(function (array $data, User $record): void {
                app(SmsCreditService::class)->grant($record, (int) $data['credits'], $data['note'] ?? null, Auth::guard('admin')->id());
                self::notify(1, (int) $data['credits']);
            });
    }

    public static function forSelection(): BulkAction
    {
        return BulkAction::make('giveSmsBulk')
            ->label('Give SMS to selected')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('info')
            ->schema(self::schema())
            ->deselectRecordsAfterCompletion()
            ->action(function (array $data, Collection $records): void {
                $count = app(SmsCreditService::class)->grantMany($records, (int) $data['credits'], $data['note'] ?? null, Auth::guard('admin')->id());
                self::notify($count, (int) $data['credits']);
            });
    }

    public static function forEveryone(): Action
    {
        return Action::make('giveSmsAll')
            ->label('Give SMS to all users')
            ->icon('heroicon-o-megaphone')
            ->color('info')
            ->schema(self::schema())
            ->requiresConfirmation()
            ->modalDescription('Every user account (active or not) receives these credits.')
            ->action(function (array $data): void {
                $service = app(SmsCreditService::class);
                $count = 0;
                User::query()->chunkById(200, function ($users) use ($service, $data, &$count) {
                    $count += $service->grantMany($users, (int) $data['credits'], $data['note'] ?? 'Given to all users', Auth::guard('admin')->id());
                });
                self::notify($count, (int) $data['credits']);
            });
    }
}
