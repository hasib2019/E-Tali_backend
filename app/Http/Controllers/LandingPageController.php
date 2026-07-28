<?php

namespace App\Http\Controllers;

use App\Models\LandingSection;
use App\Models\Package;
use App\Support\CategoryRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        $locale = request()->string('lang')->lower()->value();

        if (in_array($locale, ['bn', 'en'], true)) {
            session(['landing_locale' => $locale]);
        } else {
            $locale = session('landing_locale', 'bn');
        }

        App::setLocale($locale);

        $sections = $this->landingSections();
        // Load the complete translation group before adding partial CMS lines;
        // otherwise the translator would treat the first override as the group.
        Lang::get('landing');
        $this->applyTranslationOverrides($sections, $locale);

        $features = $this->localizedCards(
            $sections->get('features'),
            $locale,
            config('landing.features', []),
        );
        $categories = $this->localizedCategories(
            $sections->get('categories'),
            $locale,
            config('landing.categories', []),
        );
        $faqs = $this->localizedFaqs(
            $sections->get('faq'),
            $locale,
            config('landing.faqs', []),
        );
        $pillarItems = $this->localizedCards(
            $sections->get('pillars'),
            $locale,
            [],
        );
        if ($pillarItems === []) {
            $pillarItems = collect([
                ['key' => 'record', 'icon' => 'receipt'],
                ['key' => 'understand', 'icon' => 'chart'],
                ['key' => 'grow', 'icon' => 'arrow'],
            ])->map(fn (array $item): array => [
                'icon' => $item['icon'],
                'title_text' => __("landing.pillars.items.{$item['key']}.title"),
                'description_text' => __("landing.pillars.items.{$item['key']}.description"),
            ])->all();
        }

        $stepItems = collect(Arr::get($sections->get('steps')?->content ?? [], 'items', []))
            ->map(fn (array $item): array => [
                'title' => (string) ($item["title_{$locale}"] ?? ''),
                'description' => (string) ($item["description_{$locale}"] ?? ''),
            ])
            ->filter(fn (array $item): bool => $item['title'] !== '')
            ->values()
            ->all();
        if ($stepItems === []) {
            $stepItems = __('landing.steps.items');
        }

        $statsSection = $sections->get('stats');
        $statsContent = $statsSection?->content ?? [];
        $stats = $statsSection && ! Arr::get($statsContent, 'use_live', true)
            ? collect(Arr::get($statsContent, 'items', []))
                ->map(fn (array $item): array => [
                    'value' => (string) ($item['value'] ?? ''),
                    'label' => (string) ($item["label_{$locale}"] ?? ''),
                ])
                ->filter(fn (array $item): bool => $item['value'] !== '' && $item['label'] !== '')
                ->take(4)
                ->values()
                ->all()
            : $this->stats();

        if ($stats === []) {
            $stats = $this->capabilityStats();
        }

        $globalContent = $sections->get('global')?->content ?? [];
        $siteSettings = array_merge(
            [
                'support_phone' => config('landing.support_phone'),
                'support_email' => config('landing.support_email'),
                'company_address' => config('landing.company_address'),
                'android_url' => config('landing.android_url'),
                'web_app_url' => config('landing.web_app_url'),
                'facebook_url' => config('landing.facebook_url'),
                'youtube_url' => config('landing.youtube_url'),
                'linkedin_url' => config('landing.linkedin_url'),
            ],
            array_filter(
                Arr::get($globalContent, 'settings', []),
                static fn (mixed $value): bool => filled($value),
            ),
        );

        $heroImageUrl = filled($sections->get('hero')?->image_path)
            ? Storage::disk('public')->url($sections->get('hero')->image_path)
            : asset('images/landing/merchant-app.webp');

        $sectionVisibility = collect([
            'global', 'hero', 'stats', 'pillars', 'features', 'ledger', 'categories',
            'personal', 'steps', 'backup', 'pricing', 'faq', 'cta',
        ])->mapWithKeys(fn (string $key): array => [
            $key => $sections->has($key) ? (bool) $sections->get($key)->is_active : true,
        ])->all();

        return view('welcome', [
            'locale' => $locale,
            'features' => $features,
            'categories' => $categories,
            'faqs' => $faqs,
            'pillarItems' => $pillarItems,
            'stepItems' => $stepItems,
            'packages' => $this->packages(),
            'stats' => $stats,
            'categoryCount' => count(CategoryRegistry::CATEGORIES),
            'sectionVisibility' => $sectionVisibility,
            'siteSettings' => $siteSettings,
            'heroImageUrl' => $heroImageUrl,
        ]);
    }

    /**
     * @return Collection<string, LandingSection>
     */
    private function landingSections(): Collection
    {
        try {
            if (! Schema::hasTable('landing_sections')) {
                return collect();
            }

            return LandingSection::query()
                ->orderBy('sort_order')
                ->get()
                ->keyBy('key');
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Keep the Blade template's translation calls intact while replacing the
     * selected locale's content with section-level CMS values.
     *
     * @param  Collection<string, LandingSection>  $sections
     */
    private function applyTranslationOverrides(Collection $sections, string $locale): void
    {
        $prefixes = [
            'hero' => 'hero',
            'pillars' => 'pillars',
            'features' => 'features_section',
            'ledger' => 'showcase.ledger',
            'categories' => 'categories_section',
            'personal' => 'showcase.personal',
            'steps' => 'steps',
            'backup' => 'showcase.backup',
            'pricing' => 'pricing',
            'faq' => 'faq',
            'cta' => 'cta',
        ];

        foreach ($prefixes as $sectionKey => $translationPrefix) {
            $content = Arr::get($sections->get($sectionKey)?->content ?? [], $locale, []);

            if (! is_array($content)) {
                continue;
            }

            if ($sectionKey === 'hero' && isset($content['title'])) {
                $content['title'] = strip_tags((string) $content['title'], ['span']);
            }

            $lines = collect(Arr::dot($content))
                ->mapWithKeys(fn (mixed $value, string $key): array => [
                    "landing.{$translationPrefix}.{$key}" => $value,
                ])
                ->all();

            Lang::addLines($lines, $locale);
        }

        $global = Arr::get($sections->get('global')?->content ?? [], $locale, []);
        if (is_array($global)) {
            $globalLines = array_filter([
                'landing.meta.title' => $global['meta_title'] ?? null,
                'landing.meta.description' => $global['meta_description'] ?? null,
                'landing.brand_tagline' => $global['brand_tagline'] ?? null,
                'landing.footer.description' => $global['footer_description'] ?? null,
            ], static fn (mixed $value): bool => filled($value));

            foreach (['nav', 'footer'] as $group) {
                foreach (Arr::dot(Arr::get($global, $group, [])) as $key => $value) {
                    if (filled($value)) {
                        $globalLines["landing.{$group}.{$key}"] = $value;
                    }
                }
            }

            Lang::addLines($globalLines, $locale);
        }

    }

    /**
     * @param  array<int, array<string, mixed>>  $fallback
     * @return array<int, array<string, mixed>>
     */
    private function localizedCards(?LandingSection $section, string $locale, array $fallback): array
    {
        $items = Arr::get($section?->content ?? [], 'items', []);

        if (! is_array($items) || $items === []) {
            return $fallback;
        }

        return collect($items)->map(fn (array $item): array => [
            ...$item,
            'title_text' => (string) ($item["title_{$locale}"] ?? ''),
            'description_text' => (string) ($item["description_{$locale}"] ?? ''),
        ])->filter(fn (array $item): bool => $item['title_text'] !== '')->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fallback
     * @return array<int, array<string, mixed>>
     */
    private function localizedCategories(?LandingSection $section, string $locale, array $fallback): array
    {
        $items = Arr::get($section?->content ?? [], 'items', []);

        if (! is_array($items) || $items === []) {
            return $fallback;
        }

        return collect($items)->map(fn (array $item): array => [
            ...$item,
            'label_text' => (string) ($item["label_{$locale}"] ?? ''),
        ])->filter(fn (array $item): bool => $item['label_text'] !== '')->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fallback
     * @return array<int, array<string, mixed>>
     */
    private function localizedFaqs(?LandingSection $section, string $locale, array $fallback): array
    {
        $items = Arr::get($section?->content ?? [], 'items', []);

        if (! is_array($items) || $items === []) {
            return $fallback;
        }

        return collect($items)->map(fn (array $item): array => [
            'question_text' => (string) ($item["question_{$locale}"] ?? ''),
            'answer_text' => (string) ($item["answer_{$locale}"] ?? ''),
        ])->filter(fn (array $item): bool => $item['question_text'] !== '')->values()->all();
    }

    /**
     * Active plans are managed through the existing Filament Package resource.
     * Static translated plans keep the public page usable before first migrate.
     *
     * @return array<int, array<string, mixed>>
     */
    private function packages(): array
    {
        try {
            if (! Schema::hasTable('packages')) {
                return $this->fallbackPackages();
            }

            $packages = Package::query()
                ->where('is_active', true)
                ->orderBy('price')
                ->get();

            if ($packages->isEmpty()) {
                return $this->fallbackPackages();
            }

            return $packages->values()->map(function (Package $package, int $index): array {
                $slug = match (strtolower($package->name)) {
                    'free trial' => 'trial',
                    'monthly' => 'monthly',
                    'yearly' => 'yearly',
                    default => null,
                };

                $features = [];
                if ($package->max_businesses === null) {
                    $features[] = __('landing.pricing.unlimited_businesses');
                } else {
                    $features[] = trans_choice(
                        'landing.pricing.business_limit',
                        $package->max_businesses,
                        ['count' => $this->number($package->max_businesses)],
                    );
                }
                $features[] = $package->max_parties === null
                    ? __('landing.pricing.unlimited_parties')
                    : trans_choice(
                        'landing.pricing.party_limit',
                        $package->max_parties,
                        ['count' => $this->number($package->max_parties)],
                    );
                $features[] = in_array('backup', $package->features ?? [], true)
                    ? __('landing.pricing.cloud_backup')
                    : __('landing.pricing.core_tools');

                return [
                    'name' => $slug ? __("landing.pricing.plans.{$slug}.name") : $package->name,
                    'description' => $slug
                        ? __("landing.pricing.plans.{$slug}.description")
                        : ($package->description ?: __('landing.pricing.default_description')),
                    'price' => (float) $package->price,
                    'duration_days' => $package->duration_days,
                    'features' => $features,
                    'featured' => $slug === 'yearly' || ($slug === null && $index === 1),
                ];
            })->all();
        } catch (Throwable) {
            return $this->fallbackPackages();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fallbackPackages(): array
    {
        return [
            [
                'name' => __('landing.pricing.plans.trial.name'),
                'description' => __('landing.pricing.plans.trial.description'),
                'price' => 0,
                'duration_days' => 14,
                'features' => [
                    trans_choice('landing.pricing.business_limit', 1, ['count' => $this->number(1)]),
                    trans_choice('landing.pricing.party_limit', 50, ['count' => $this->number(50)]),
                    __('landing.pricing.core_tools'),
                ],
                'featured' => false,
            ],
            [
                'name' => __('landing.pricing.plans.monthly.name'),
                'description' => __('landing.pricing.plans.monthly.description'),
                'price' => 199,
                'duration_days' => 30,
                'features' => [
                    trans_choice('landing.pricing.business_limit', 10, ['count' => $this->number(10)]),
                    __('landing.pricing.unlimited_parties'),
                    __('landing.pricing.cloud_backup'),
                ],
                'featured' => false,
            ],
            [
                'name' => __('landing.pricing.plans.yearly.name'),
                'description' => __('landing.pricing.plans.yearly.description'),
                'price' => 1999,
                'duration_days' => 365,
                'features' => [
                    __('landing.pricing.unlimited_businesses'),
                    __('landing.pricing.unlimited_parties'),
                    __('landing.pricing.cloud_backup'),
                ],
                'featured' => true,
            ],
        ];
    }

    /**
     * Uses real platform totals when available. Capability totals are an honest
     * fallback for a fresh install, rather than publishing made-up user counts.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function stats(): array
    {
        try {
            $tables = ['users', 'businesses', 'transactions'];
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    return $this->capabilityStats();
                }
            }

            $counts = [
                'users' => DB::table('users')->count(),
                'businesses' => DB::table('businesses')->count(),
                'transactions' => DB::table('transactions')->count(),
            ];

            if (array_sum($counts) === 0) {
                return $this->capabilityStats();
            }

            return [
                ['value' => $this->compactNumber($counts['users']), 'label' => __('landing.stats.users')],
                ['value' => $this->compactNumber($counts['businesses']), 'label' => __('landing.stats.khatas')],
                ['value' => $this->compactNumber($counts['transactions']), 'label' => __('landing.stats.transactions')],
                ['value' => $this->number(count(CategoryRegistry::CATEGORIES)), 'label' => __('landing.stats.categories')],
            ];
        } catch (Throwable) {
            return $this->capabilityStats();
        }
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function capabilityStats(): array
    {
        return [
            ['value' => $this->number(count(CategoryRegistry::CATEGORIES)), 'label' => __('landing.stats.categories')],
            ['value' => $this->number(8).'+', 'label' => __('landing.stats.core_tools')],
            ['value' => $this->number(2), 'label' => __('landing.stats.languages')],
            ['value' => '24/7', 'label' => __('landing.stats.access')],
        ];
    }

    private function compactNumber(int $number): string
    {
        $value = match (true) {
            $number >= 1_000_000 => round($number / 1_000_000, 1).'M+',
            $number >= 1_000 => round($number / 1_000, 1).'K+',
            default => (string) $number,
        };

        return App::isLocale('bn') ? $this->toBengaliDigits($value) : $value;
    }

    private function number(int $number): string
    {
        $value = (string) $number;

        return App::isLocale('bn') ? $this->toBengaliDigits($value) : $value;
    }

    private function toBengaliDigits(string $value): string
    {
        return strtr($value, [
            '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
            '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
        ]);
    }
}
