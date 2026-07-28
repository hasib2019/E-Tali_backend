@if ($sectionVisibility['global'])
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand-light" href="{{ route('home') }}#home">
                <img src="{{ asset('images/landing/app-mark.png') }}" alt="" width="48" height="48">
                <span><strong>ই-টালি-খাতা</strong><small>{{ __('landing.brand_tagline') }}</small></span>
            </a>
            <p>{{ __('landing.footer.description') }}</p>
            @if ($siteSettings['facebook_url'] || $siteSettings['youtube_url'] || $siteSettings['linkedin_url'])
                <div class="social-links">
                    @foreach (['facebook' => 'f', 'youtube' => '▶', 'linkedin' => 'in'] as $network => $label)
                        @if ($siteSettings["{$network}_url"])
                            <a href="{{ $siteSettings["{$network}_url"] }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}">{{ $label }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <h3>{{ __('landing.footer.product') }}</h3>
            @if ($sectionVisibility['features'])
            <a href="{{ route('home') }}#features">{{ __('landing.nav.features') }}</a>
            @endif
            @if ($sectionVisibility['categories'])
            <a href="{{ route('home') }}#solutions">{{ __('landing.nav.solutions') }}</a>
            @endif
            @if ($sectionVisibility['pricing'])
            <a href="{{ route('home') }}#pricing">{{ __('landing.nav.pricing') }}</a>
            @endif
            @if ($sectionVisibility['faq'])
            <a href="{{ route('home') }}#faq">{{ __('landing.nav.faq') }}</a>
            @endif
        </div>
        <div>
            <h3>{{ __('landing.footer.company') }}</h3>
            <span>{{ __('landing.footer.support') }}: {{ $siteSettings['support_phone'] }}</span>
            <a href="mailto:{{ $siteSettings['support_email'] }}">{{ $siteSettings['support_email'] }}</a>
            <span>{{ __('landing.footer.address') }}: {{ $siteSettings['company_address'] }}</span>
        </div>
        <div>
            <h3>{{ __('landing.footer.legal') }}</h3>
            <a href="{{ route('privacy') }}">{{ __('landing.footer.privacy') }}</a>
            <a href="{{ route('terms') }}">{{ __('landing.footer.terms') }}</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => $locale === 'bn' ? 'en' : 'bn']) }}">{{ $locale === 'bn' ? 'English' : 'বাংলা' }}</a>
            <a href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">{{ __('landing.nav.download') }}</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© {{ now()->year }} E-Tali-Khata. {{ __('landing.footer.rights') }}</span>
        <span>Developed By <a href="https://creativeitbari.com/" target="_blank" rel="noopener">CreativeITbari</a></span>
    </div>
</footer>
@endif
