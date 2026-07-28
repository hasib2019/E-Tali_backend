<!DOCTYPE html>
<html lang="{{ $locale }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#087a5b">
    <meta name="description" content="Privacy Policy for E-Tali-Khata, including how we handle Google account and Google Drive data.">

    <title>{{ __('legal.privacy.title') }} — E-Tali-Khata</title>

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('legal.privacy.title') }} — E-Tali-Khata">
    <meta property="og:description" content="Privacy Policy for E-Tali-Khata, including how we handle Google account and Google Drive data.">
    <meta property="og:url" content="{{ url()->current() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/landing/app-mark.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body>
    <a class="skip-link" href="#main">{{ $locale === 'bn' ? 'মূল কনটেন্টে যান' : 'Skip to content' }}</a>

    @include('partials.site-header')

    <main id="main">
        <section class="legal-page">
            <div class="container">
                <div class="legal-masthead">
                    <h1>{{ __('legal.privacy.title') }}</h1>
                    <p class="legal-effective">{{ __('legal.privacy.effective_date') }}</p>
                </div>

                <p class="legal-lead">{!! __('legal.privacy.lead') !!}</p>

                @foreach (__('legal.privacy.sections') as $section)
                    <h2>{!! $section['heading'] !!}</h2>
                    {!! $section['body'] !!}
                @endforeach

                <h2>{{ __('legal.privacy.contact_heading') }}</h2>
                <p>{{ __('legal.privacy.contact_intro') }} <a href="mailto:{{ $siteSettings['support_email'] }}">{{ $siteSettings['support_email'] }}</a></p>
            </div>
        </section>
    </main>

    @include('partials.site-footer')

    <a class="floating-download" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener" aria-label="{{ __('landing.nav.download') }}">
        <x-landing-icon name="download" size="22"/>
        <span>{{ __('landing.nav.download') }}</span>
    </a>

    <script src="{{ asset('js/landing.js') }}" defer></script>
</body>
</html>
