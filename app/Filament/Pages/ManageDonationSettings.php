<?php

namespace App\Filament\Pages;

use App\Models\DonationSetting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Singleton settings page for the "Support us" screen: bKash number + intro copy. */
class ManageDonationSettings extends Page
{
    protected string $view = 'filament.pages.manage-donation-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Support Us Settings';

    protected static ?string $title = 'Support Us Settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->data = DonationSetting::current()->only(['bkash_number', 'intro_title', 'intro_message']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bkash_number')
                    ->label('bKash number (Send Money)')
                    ->helperText('Shown to users on the Support Us screen for every donation tier.')
                    ->required()
                    ->maxLength(20),
                TextInput::make('intro_title')
                    ->label('Screen title')
                    ->maxLength(80),
                Textarea::make('intro_message')
                    ->label('Screen message')
                    ->rows(4),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        DonationSetting::current()->update($data);

        Notification::make()
            ->title('Saved')
            ->success()
            ->send();
    }
}
