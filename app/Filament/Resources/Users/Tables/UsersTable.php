<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean(),
                TextColumn::make('provider')
                    ->badge()
                    ->colors(['gray' => 'email', 'info' => 'google']),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->placeholder('—'),
                TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'none',
                        'success' => 'active',
                        'danger' => 'expired',
                    ]),
                TextColumn::make('subscription_expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean(),
                TextColumn::make('backup_frequency')
                    ->label('Backup')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                SelectFilter::make('subscription_status')
                    ->options([
                        'none' => 'None',
                        'active' => 'Active',
                        'expired' => 'Expired',
                    ]),
                SelectFilter::make('provider')
                    ->options(['email' => 'Email', 'google' => 'Google']),
            ])
            ->recordActions([
                self::assignPackageAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    /**
     * Assign or renew a subscription package for the user: records an audit
     * row in `subscriptions` and syncs the denormalized snapshot on `users`.
     */
    protected static function assignPackageAction(): Action
    {
        return Action::make('assignPackage')
            ->label('Assign / Renew')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->schema([
                Select::make('package_id')
                    ->label('Package')
                    ->options(fn () => Package::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                DateTimePicker::make('custom_expiry')
                    ->label('Custom expiry (optional)')
                    ->helperText('Leave blank to grant the package duration from today.'),
                Toggle::make('mark_paid')
                    ->label('Mark as paid')
                    ->default(true),
            ])
            ->action(function (array $data, User $record): void {
                $package = Package::findOrFail($data['package_id']);

                $startsAt = Carbon::now();
                $expiresAt = ! empty($data['custom_expiry'])
                    ? Carbon::parse($data['custom_expiry'])
                    : (clone $startsAt)->addDays($package->duration_days);

                Subscription::create([
                    'user_id' => $record->id,
                    'package_id' => $package->id,
                    'starts_at' => $startsAt,
                    'expires_at' => $expiresAt,
                    'amount' => $package->price,
                    'status' => 'active',
                    'note' => 'Assigned via admin panel',
                    'created_by_admin_id' => Auth::guard('admin')->id(),
                ]);

                $record->update([
                    'package_id' => $package->id,
                    'subscription_status' => 'active',
                    'subscribed_at' => $startsAt,
                    'subscription_expires_at' => $expiresAt,
                    'is_paid' => (bool) ($data['mark_paid'] ?? true),
                ]);

                Notification::make()
                    ->success()
                    ->title('Package assigned')
                    ->body("{$record->name} is now on {$package->name} until {$expiresAt->format('d M Y')}.")
                    ->send();
            });
    }
}
