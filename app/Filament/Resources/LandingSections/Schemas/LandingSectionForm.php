<?php

namespace App\Filament\Resources\LandingSections\Schemas;

use App\Models\LandingSection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LandingSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(fn (?LandingSection $record): array => [
                self::sectionControls(),
                self::contentFor($record?->key),
            ]);
    }

    private static function sectionControls(): Section
    {
        return Section::make('Section controls')
            ->description('The section key is permanent. Turn visibility off to hide this section without deleting its content.')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('name')
                        ->label('Admin label')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('key')
                        ->disabled()
                        ->dehydrated()
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Visible on landing page')
                        ->default(true),
                ]),
                Textarea::make('description')
                    ->label('Admin note')
                    ->rows(2)
                    ->maxLength(255),
            ]);
    }

    private static function contentFor(?string $key): Section
    {
        return match ($key) {
            'global' => self::globalSettings(),
            'hero' => self::hero(),
            'stats' => self::stats(),
            'pillars' => self::pillars(),
            'features' => self::features(),
            'ledger' => self::showcase('ledger', 'Ledger showcase content'),
            'categories' => self::categories(),
            'personal' => self::showcase('personal', 'Adaptive experience content'),
            'steps' => self::steps(),
            'backup' => self::showcase('backup', 'Backup showcase content'),
            'pricing' => self::pricing(),
            'faq' => self::faq(),
            'cta' => self::cta(),
            default => Section::make('Content')->schema([]),
        };
    }

    private static function globalSettings(): Section
    {
        return Section::make('Global website settings')
            ->visible(fn (Get $get): bool => $get('key') === 'global')
            ->schema([
                self::localizedTabs([
                    ['meta_title', 'SEO title', 'text'],
                    ['meta_description', 'SEO description', 'textarea'],
                    ['brand_tagline', 'Brand tagline', 'text'],
                    ['footer_description', 'Footer description', 'textarea'],
                    ['nav.home', 'Navigation: Home', 'text'],
                    ['nav.features', 'Navigation: Features', 'text'],
                    ['nav.solutions', 'Navigation: Solutions', 'text'],
                    ['nav.pricing', 'Navigation: Pricing', 'text'],
                    ['nav.faq', 'Navigation: FAQ', 'text'],
                    ['nav.download', 'Navigation: Download button', 'text'],
                    ['footer.product', 'Footer: Product heading', 'text'],
                    ['footer.company', 'Footer: Contact heading', 'text'],
                    ['footer.legal', 'Footer: Useful heading', 'text'],
                    ['footer.support', 'Footer: Support label', 'text'],
                    ['footer.address', 'Footer: Address label', 'text'],
                    ['footer.rights', 'Footer: Copyright text', 'text'],
                ]),
                Section::make('Contact, download and social links')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('content.settings.support_phone')
                                ->label('Support phone')
                                ->tel(),
                            TextInput::make('content.settings.support_email')
                                ->label('Support email')
                                ->email(),
                            TextInput::make('content.settings.company_address')
                                ->label('Company address'),
                            TextInput::make('content.settings.android_url')
                                ->label('Play Store / download URL')
                                ->url(),
                            TextInput::make('content.settings.web_app_url')
                                ->label('Web app or deep-link URL'),
                            TextInput::make('content.settings.facebook_url')
                                ->label('Facebook URL')
                                ->url(),
                            TextInput::make('content.settings.youtube_url')
                                ->label('YouTube URL')
                                ->url(),
                            TextInput::make('content.settings.linkedin_url')
                                ->label('LinkedIn URL')
                                ->url(),
                        ]),
                    ]),
            ]);
    }

    private static function hero(): Section
    {
        return Section::make('Hero content and image')
            ->visible(fn (Get $get): bool => $get('key') === 'hero')
            ->schema([
                FileUpload::make('image_path')
                    ->label('Merchant hero image')
                    ->helperText('Optional. If empty, the original landing image remains in use. Recommended: landscape WebP/JPG, at least 1400px wide.')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('landing')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->columnSpanFull(),
                Tabs::make('Hero languages')
                    ->tabs([
                        self::heroTab('বাংলা', 'bn'),
                        self::heroTab('English', 'en'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function heroTab(string $label, string $locale): Tab
    {
        return Tab::make($label)
            ->schema([
                TextInput::make("content.{$locale}.eyebrow")
                    ->label('Small heading')
                    ->required(),
                Textarea::make("content.{$locale}.title")
                    ->label('Main headline')
                    ->helperText('Wrap the highlighted words in <span>…</span>. Only this span tag is rendered.')
                    ->rows(2)
                    ->required(),
                Textarea::make("content.{$locale}.description")
                    ->label('Description')
                    ->rows(3)
                    ->required(),
                Grid::make(2)->schema([
                    TextInput::make("content.{$locale}.primary_cta")
                        ->label('Primary button'),
                    TextInput::make("content.{$locale}.secondary_cta")
                        ->label('Secondary button'),
                    TextInput::make("content.{$locale}.trust_one")
                        ->label('Trust point 1'),
                    TextInput::make("content.{$locale}.trust_two")
                        ->label('Trust point 2'),
                    TextInput::make("content.{$locale}.trust_three")
                        ->label('Trust point 3'),
                ]),
                Section::make('Dashboard preview labels')
                    ->collapsed()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make("content.{$locale}.visual_badge")->label('Preview heading'),
                            TextInput::make("content.{$locale}.receivable")->label('Receivable'),
                            TextInput::make("content.{$locale}.payable")->label('Payable'),
                            TextInput::make("content.{$locale}.cash")->label('Cash balance'),
                            TextInput::make("content.{$locale}.recent")->label('Recent activity'),
                            TextInput::make("content.{$locale}.sale")->label('Sale'),
                            TextInput::make("content.{$locale}.expense")->label('Expense'),
                            TextInput::make("content.{$locale}.paid")->label('Payment received'),
                            TextInput::make("content.{$locale}.sync")->label('Backup badge'),
                        ]),
                    ]),
            ]);
    }

    private static function stats(): Section
    {
        return Section::make('Statistics')
            ->visible(fn (Get $get): bool => $get('key') === 'stats')
            ->schema([
                Toggle::make('content.use_live')
                    ->label('Use live platform statistics')
                    ->helperText('When enabled, real users, khatas and transaction totals are used. Fresh installs show capability totals.')
                    ->live(),
                Repeater::make('content.items')
                    ->label('Custom statistics')
                    ->helperText('Used only when live statistics are turned off.')
                    ->visible(fn (Get $get): bool => ! (bool) $get('content.use_live'))
                    ->schema([
                        TextInput::make('value')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('label_bn')
                            ->label('বাংলা label')
                            ->required(),
                        TextInput::make('label_en')
                            ->label('English label')
                            ->required(),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->maxItems(4)
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }

    private static function pillars(): Section
    {
        return Section::make('Overview cards')
            ->visible(fn (Get $get): bool => $get('key') === 'pillars')
            ->schema([
                self::localizedHeadingTabs(),
                self::cardRepeater('content.items', 'Overview cards'),
            ]);
    }

    private static function features(): Section
    {
        return Section::make('Feature cards')
            ->visible(fn (Get $get): bool => $get('key') === 'features')
            ->schema([
                self::localizedHeadingTabs(),
                self::cardRepeater('content.items', 'Features'),
            ]);
    }

    private static function categories(): Section
    {
        return Section::make('Khata categories')
            ->visible(fn (Get $get): bool => $get('key') === 'categories')
            ->schema([
                self::localizedHeadingTabs(),
                Repeater::make('content.items')
                    ->label('Category cards')
                    ->schema([
                        TextInput::make('key')
                            ->label('Internal key')
                            ->required()
                            ->maxLength(50),
                        Select::make('icon')
                            ->options(self::iconOptions())
                            ->searchable()
                            ->required(),
                        TextInput::make('label_bn')
                            ->label('বাংলা label')
                            ->required(),
                        TextInput::make('label_en')
                            ->label('English label')
                            ->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label_bn'] ?? $state['label_en'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    private static function showcase(string $key, string $heading): Section
    {
        return Section::make($heading)
            ->visible(fn (Get $get): bool => $get('key') === $key)
            ->schema([
                Tabs::make('Languages')
                    ->tabs([
                        self::showcaseTab('বাংলা', 'bn'),
                        self::showcaseTab('English', 'en'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function showcaseTab(string $label, string $locale): Tab
    {
        return Tab::make($label)
            ->schema([
                TextInput::make("content.{$locale}.eyebrow")
                    ->label('Small heading')
                    ->required(),
                TextInput::make("content.{$locale}.title")
                    ->label('Title')
                    ->required(),
                Textarea::make("content.{$locale}.description")
                    ->label('Description')
                    ->rows(3)
                    ->required(),
                Repeater::make("content.{$locale}.points")
                    ->label('Check-list points')
                    ->simple(
                        TextInput::make('point')
                            ->required(),
                    )
                    ->minItems(1)
                    ->reorderable(),
            ]);
    }

    private static function steps(): Section
    {
        return Section::make('Getting started steps')
            ->visible(fn (Get $get): bool => $get('key') === 'steps')
            ->schema([
                Tabs::make('Section heading')
                    ->tabs([
                        Tab::make('বাংলা')->schema([
                            TextInput::make('content.bn.eyebrow')->label('Small heading')->required(),
                            TextInput::make('content.bn.title')->label('Title')->required(),
                        ]),
                        Tab::make('English')->schema([
                            TextInput::make('content.en.eyebrow')->label('Small heading')->required(),
                            TextInput::make('content.en.title')->label('Title')->required(),
                        ]),
                    ])
                    ->columnSpanFull(),
                Repeater::make('content.items')
                    ->label('Steps')
                    ->schema([
                        TextInput::make('title_bn')->label('বাংলা title')->required(),
                        TextInput::make('title_en')->label('English title')->required(),
                        Textarea::make('description_bn')->label('বাংলা description')->rows(2)->required(),
                        Textarea::make('description_en')->label('English description')->rows(2)->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->maxItems(6)
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title_bn'] ?? $state['title_en'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    private static function pricing(): Section
    {
        return Section::make('Pricing section heading')
            ->description('Plan name, price, duration and limits are edited under Subscriptions → Packages.')
            ->visible(fn (Get $get): bool => $get('key') === 'pricing')
            ->schema([
                self::localizedHeadingTabs(),
            ]);
    }

    private static function faq(): Section
    {
        return Section::make('Frequently asked questions')
            ->visible(fn (Get $get): bool => $get('key') === 'faq')
            ->schema([
                Tabs::make('Section heading')
                    ->tabs([
                        Tab::make('বাংলা')->schema([
                            TextInput::make('content.bn.eyebrow')->label('Small heading')->required(),
                            TextInput::make('content.bn.title')->label('Title')->required(),
                        ]),
                        Tab::make('English')->schema([
                            TextInput::make('content.en.eyebrow')->label('Small heading')->required(),
                            TextInput::make('content.en.title')->label('Title')->required(),
                        ]),
                    ])
                    ->columnSpanFull(),
                Repeater::make('content.items')
                    ->label('Questions')
                    ->schema([
                        TextInput::make('question_bn')->label('বাংলা question')->required(),
                        TextInput::make('question_en')->label('English question')->required(),
                        Textarea::make('answer_bn')->label('বাংলা answer')->rows(3)->required(),
                        Textarea::make('answer_en')->label('English answer')->rows(3)->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['question_bn'] ?? $state['question_en'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    private static function cta(): Section
    {
        return Section::make('Final download call-to-action')
            ->visible(fn (Get $get): bool => $get('key') === 'cta')
            ->schema([
                Tabs::make('Languages')
                    ->tabs([
                        self::ctaTab('বাংলা', 'bn'),
                        self::ctaTab('English', 'en'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function ctaTab(string $label, string $locale): Tab
    {
        return Tab::make($label)
            ->schema([
                TextInput::make("content.{$locale}.eyebrow")->label('Small heading')->required(),
                TextInput::make("content.{$locale}.title")->label('Title')->required(),
                Textarea::make("content.{$locale}.description")->label('Description')->rows(3)->required(),
                TextInput::make("content.{$locale}.button")->label('Button label')->required(),
            ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $fields
     */
    private static function localizedTabs(array $fields): Tabs
    {
        return Tabs::make('Languages')
            ->tabs([
                Tab::make('বাংলা')->schema(self::localizedFields('bn', $fields)),
                Tab::make('English')->schema(self::localizedFields('en', $fields)),
            ])
            ->columnSpanFull();
    }

    private static function localizedHeadingTabs(): Tabs
    {
        return self::localizedTabs([
            ['eyebrow', 'Small heading', 'text'],
            ['title', 'Title', 'text'],
            ['description', 'Description', 'textarea'],
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $fields
     * @return array<int, TextInput|Textarea>
     */
    private static function localizedFields(string $locale, array $fields): array
    {
        return array_map(function (array $field) use ($locale): TextInput|Textarea {
            [$key, $label, $type] = $field;
            $component = $type === 'textarea'
                ? Textarea::make("content.{$locale}.{$key}")->rows(3)
                : TextInput::make("content.{$locale}.{$key}");

            return $component
                ->label($label)
                ->required();
        }, $fields);
    }

    private static function cardRepeater(string $path, string $label): Repeater
    {
        return Repeater::make($path)
            ->label($label)
            ->schema([
                Select::make('icon')
                    ->options(self::iconOptions())
                    ->searchable()
                    ->required(),
                TextInput::make('title_bn')
                    ->label('বাংলা title')
                    ->required(),
                TextInput::make('title_en')
                    ->label('English title')
                    ->required(),
                Textarea::make('description_bn')
                    ->label('বাংলা description')
                    ->rows(2)
                    ->required(),
                Textarea::make('description_en')
                    ->label('English description')
                    ->rows(2)
                    ->required(),
            ])
            ->columns(2)
            ->minItems(1)
            ->reorderable()
            ->collapsible()
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => $state['title_bn'] ?? $state['title_en'] ?? null)
            ->columnSpanFull();
    }

    /**
     * @return array<string, string>
     */
    private static function iconOptions(): array
    {
        return [
            'users' => 'Users / community',
            'wallet' => 'Wallet / cashbook',
            'receipt' => 'Receipt / voucher',
            'package' => 'Package / stock',
            'chart' => 'Chart / report',
            'bell' => 'Bell / reminder',
            'cloud' => 'Cloud / backup',
            'shield' => 'Shield / security',
            'store' => 'Store',
            'briefcase' => 'Briefcase',
            'education' => 'Education',
            'home' => 'Home',
            'plane' => 'Plane',
            'bike' => 'Bike',
            'building' => 'Building',
            'community' => 'Community',
            'meal' => 'Meal',
            'laptop' => 'Laptop',
            'tools' => 'Tools',
            'wifi' => 'Wi-Fi',
            'tv' => 'Television',
            'book' => 'Book',
            'arrow' => 'Arrow / growth',
        ];
    }
}
