<?php

namespace App\Support;

use App\Models\LandingSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Site-wide chrome (locale, header/footer settings, section visibility) shared
 * between the marketing landing page and standalone pages like Privacy/Terms
 * that reuse the same header and footer.
 */
class LandingChrome
{
    public static function resolveLocale(): string
    {
        $locale = request()->string('lang')->lower()->value();

        if (in_array($locale, ['bn', 'en'], true)) {
            session(['landing_locale' => $locale]);
        } else {
            $locale = session('landing_locale', 'bn');
        }

        App::setLocale($locale);

        return $locale;
    }

    /**
     * @return Collection<string, LandingSection>
     */
    public static function sections(): Collection
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
     * @param  Collection<string, LandingSection>  $sections
     * @return array<string, mixed>
     */
    public static function siteSettings(Collection $sections): array
    {
        $globalContent = $sections->get('global')?->content ?? [];

        return array_merge(
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
    }

    /**
     * @param  Collection<string, LandingSection>  $sections
     * @return array<string, bool>
     */
    public static function sectionVisibility(Collection $sections): array
    {
        return collect([
            'global', 'hero', 'stats', 'pillars', 'features', 'ledger', 'categories',
            'personal', 'steps', 'backup', 'pricing', 'faq', 'cta',
        ])->mapWithKeys(fn (string $key): array => [
            $key => $sections->has($key) ? (bool) $sections->get($key)->is_active : true,
        ])->all();
    }
}
