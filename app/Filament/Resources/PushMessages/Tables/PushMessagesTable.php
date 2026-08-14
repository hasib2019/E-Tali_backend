<?php

namespace App\Filament\Resources\PushMessages\Tables;

use App\Jobs\SendPushMessage;
use App\Models\PushMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PushMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('audience')
                    ->label('Audience')
                    ->formatStateUsing(function ($state): string {
                        $type = is_array($state) ? ($state['type'] ?? 'all') : 'all';
                        $count = is_array($state) ? count($state['values'] ?? []) : 0;

                        return $type === 'all' ? 'All users' : ucfirst($type).($count ? " ({$count})" : '');
                    })
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'queued',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ]),
                TextColumn::make('recipient_count')->label('Recipients')->numeric(),
                TextColumn::make('sent_count')->label('Sent')->numeric()->color('success'),
                TextColumn::make('failed_count')->label('Failed')->numeric()->color('danger'),
                TextColumn::make('opened_count')->label('Opened')->numeric()->color('info'),
                TextColumn::make('sent_at')->label('Sent at')->dateTime('d M Y H:i')->placeholder('—'),
            ])
            ->recordActions([
                self::sendAction(),
                EditAction::make()
                    ->visible(fn (PushMessage $record): bool => $record->status !== 'sent'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Fan the message out to devices via Expo (sync queue = sends immediately). */
    protected static function sendAction(): Action
    {
        return Action::make('send')
            ->label('Send now')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->visible(fn (PushMessage $record): bool => $record->status !== 'sent')
            ->requiresConfirmation()
            ->modalDescription('This delivers the notification to the selected audience right now.')
            ->action(function (PushMessage $record): void {
                $record->update(['status' => 'queued']);
                SendPushMessage::dispatch($record->id);

                Notification::make()
                    ->success()
                    ->title('Notification sent')
                    ->body('Delivered to the selected audience. Check the counts in a moment.')
                    ->send();
            });
    }
}
