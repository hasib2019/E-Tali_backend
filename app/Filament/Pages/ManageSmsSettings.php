<?php

namespace App\Filament\Pages;

use App\Filament\Actions\GiveSmsAction;
use App\Models\SmsLog;
use App\Models\SmsSetting;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Singleton settings for customer SMS: master switch + free credits for new accounts. */
class ManageSmsSettings extends Page
{
    protected string $view = 'filament.pages.manage-sms-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static ?string $navigationLabel = 'SMS Settings';

    protected static ?string $title = 'SMS Settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->data = SmsSetting::current()->only(['enabled', 'free_sms_per_user']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('enabled')
                    ->label('Customer SMS enabled')
                    ->helperText('Off = nobody can send tagada / alert SMS from the app (credits are kept).'),
                TextInput::make('free_sms_per_user')
                    ->label('Free SMS for every new account')
                    ->helperText('Credits a user starts with when they sign up. Premium packages add their own "SMS quota" on assign/renew. To top up existing users use "Give SMS" below or on the Users list.')
                    ->integer()
                    ->minValue(0)
                    ->maxValue(100000)
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [GiveSmsAction::forEveryone()];
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        return [
            'Credits held by users' => (int) User::sum('sms_balance'),
            'SMS parts sent (all time)' => (int) User::sum('sms_sent_total'),
            'Sent today' => (int) SmsLog::where('type', SmsLog::TYPE_SEND)->where('status', 'sent')->whereDate('created_at', today())->sum('parts'),
        ];
    }

    public function save(): void
    {
        SmsSetting::current()->update($this->form->getState());

        Notification::make()
            ->title('Saved')
            ->success()
            ->send();
    }
}
