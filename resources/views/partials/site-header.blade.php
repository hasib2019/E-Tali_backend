@php
    $homeUrl = route('home');
    $languageSwitchUrl = request()->fullUrlWithQuery(['lang' => $locale === 'bn' ? 'en' : 'bn']);
@endphp
<header class="site-header" data-header>
    <div class="container nav-wrap">
        <a class="brand" href="{{ $homeUrl }}#home" aria-label="E-Tali-Khata">
            <img src="{{ asset('images/landing/app-mark.png') }}" alt="" width="48" height="48">
            <span>
                <strong>ই-টালি-খাতা</strong>
                <small>{{ __('landing.brand_tagline') }}</small>
            </span>
        </a>

        <nav class="desktop-nav" aria-label="Primary navigation">
            <a href="{{ $homeUrl }}#home">{{ __('landing.nav.home') }}</a>
            @if ($sectionVisibility['features'])
            <a href="{{ $homeUrl }}#features">{{ __('landing.nav.features') }}</a>
            @endif
            @if ($sectionVisibility['categories'])
            <a href="{{ $homeUrl }}#solutions">{{ __('landing.nav.solutions') }}</a>
            @endif
            @if ($sectionVisibility['pricing'])
            <a href="{{ $homeUrl }}#pricing">{{ __('landing.nav.pricing') }}</a>
            @endif
            @if ($sectionVisibility['faq'])
            <a href="{{ $homeUrl }}#faq">{{ __('landing.nav.faq') }}</a>
            @endif
        </nav>

        <div class="nav-actions">
            <a class="language-link" href="{{ $languageSwitchUrl }}" aria-label="{{ $locale === 'bn' ? 'Switch to English' : 'বাংলায় দেখুন' }}">
                <x-landing-icon name="globe" size="18"/>
                {{ $locale === 'bn' ? 'EN' : 'বাংলা' }}
            </a>
            <a class="button button-small button-primary desktop-cta" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">
                {{ __('landing.nav.download') }}
                <x-landing-icon name="download" size="18"/>
            </a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="{{ __('landing.nav.menu') }}" data-menu-toggle>
                <span class="menu-open-icon"><x-landing-icon name="menu" size="24"/></span>
                <span class="menu-close-icon"><x-landing-icon name="close" size="24"/></span>
            </button>
        </div>
    </div>

    <div class="mobile-menu" id="mobile-menu" data-mobile-menu>
        <nav class="container" aria-label="Mobile navigation">
            <a href="{{ $homeUrl }}#home">{{ __('landing.nav.home') }}</a>
            @if ($sectionVisibility['features'])
            <a href="{{ $homeUrl }}#features">{{ __('landing.nav.features') }}</a>
            @endif
            @if ($sectionVisibility['categories'])
            <a href="{{ $homeUrl }}#solutions">{{ __('landing.nav.solutions') }}</a>
            @endif
            @if ($sectionVisibility['pricing'])
            <a href="{{ $homeUrl }}#pricing">{{ __('landing.nav.pricing') }}</a>
            @endif
            @if ($sectionVisibility['faq'])
            <a href="{{ $homeUrl }}#faq">{{ __('landing.nav.faq') }}</a>
            @endif
            <a class="button button-primary" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">{{ __('landing.nav.download') }}</a>
        </nav>
    </div>
</header>
