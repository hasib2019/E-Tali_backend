<!DOCTYPE html>
<html lang="{{ $locale }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#087a5b">
    <meta name="description" content="{{ __('landing.meta.description') }}">

    <title>{{ __('landing.meta.title') }}</title>

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('landing.meta.title') }}">
    <meta property="og:description" content="{{ __('landing.meta.description') }}">
    <meta property="og:image" content="{{ $heroImageUrl }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/png" href="{{ asset('images/landing/app-mark.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'E-Tali-Khata',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Android, iOS, Web',
            'description' => __('landing.meta.description'),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'BDT',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body>
    <a class="skip-link" href="#main">{{ $locale === 'bn' ? 'মূল কনটেন্টে যান' : 'Skip to content' }}</a>

    <header class="site-header" data-header>
        <div class="container nav-wrap">
            <a class="brand" href="#home" aria-label="E-Tali-Khata">
                <img src="{{ asset('images/landing/app-mark.png') }}" alt="" width="48" height="48">
                <span>
                    <strong>ই-টালি-খাতা</strong>
                    <small>{{ __('landing.brand_tagline') }}</small>
                </span>
            </a>

            <nav class="desktop-nav" aria-label="Primary navigation">
                <a href="#home">{{ __('landing.nav.home') }}</a>
                @if ($sectionVisibility['features'])
                <a href="#features">{{ __('landing.nav.features') }}</a>
                @endif
                @if ($sectionVisibility['categories'])
                <a href="#solutions">{{ __('landing.nav.solutions') }}</a>
                @endif
                @if ($sectionVisibility['pricing'])
                <a href="#pricing">{{ __('landing.nav.pricing') }}</a>
                @endif
                @if ($sectionVisibility['faq'])
                <a href="#faq">{{ __('landing.nav.faq') }}</a>
                @endif
            </nav>

            <div class="nav-actions">
                <a class="language-link" href="{{ route('home', ['lang' => $locale === 'bn' ? 'en' : 'bn']) }}" aria-label="{{ $locale === 'bn' ? 'Switch to English' : 'বাংলায় দেখুন' }}">
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
                <a href="#home">{{ __('landing.nav.home') }}</a>
                @if ($sectionVisibility['features'])
                <a href="#features">{{ __('landing.nav.features') }}</a>
                @endif
                @if ($sectionVisibility['categories'])
                <a href="#solutions">{{ __('landing.nav.solutions') }}</a>
                @endif
                @if ($sectionVisibility['pricing'])
                <a href="#pricing">{{ __('landing.nav.pricing') }}</a>
                @endif
                @if ($sectionVisibility['faq'])
                <a href="#faq">{{ __('landing.nav.faq') }}</a>
                @endif
                <a class="button button-primary" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">{{ __('landing.nav.download') }}</a>
            </nav>
        </div>
    </header>

    <main id="main">
        @if ($sectionVisibility['hero'])
        <section class="hero" id="home">
            <div class="hero-glow hero-glow-one"></div>
            <div class="hero-glow hero-glow-two"></div>
            <div class="container hero-grid">
                <div class="hero-copy reveal">
                    <span class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        {{ __('landing.hero.eyebrow') }}
                    </span>
                    <h1>{!! __('landing.hero.title') !!}</h1>
                    <p class="hero-description">{{ __('landing.hero.description') }}</p>
                    <div class="hero-actions">
                        <a class="button button-primary button-large" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">
                            <x-landing-icon name="download" size="20"/>
                            {{ __('landing.hero.primary_cta') }}
                        </a>
                        @if ($sectionVisibility['features'])
                            <a class="button button-secondary button-large" href="#features">
                                {{ __('landing.hero.secondary_cta') }}
                                <x-landing-icon name="arrow" size="19"/>
                            </a>
                        @endif
                    </div>
                    <div class="hero-trust" aria-label="Highlights">
                        @foreach (['trust_one', 'trust_two', 'trust_three'] as $trust)
                            <span><x-landing-icon name="check" size="16"/>{{ __("landing.hero.{$trust}") }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="hero-visual reveal reveal-delay">
                    <div class="photo-frame">
                        <img src="{{ $heroImageUrl }}" alt="{{ __('landing.showcase.photo_alt') }}" width="1536" height="961" fetchpriority="high">
                        <div class="photo-shade"></div>
                    </div>

                    <div class="dashboard-card">
                        <div class="dashboard-top">
                            <div>
                                <small>{{ __('landing.hero.visual_badge') }}</small>
                                <strong>রহিম স্টোর</strong>
                            </div>
                            <span class="online-dot" aria-label="Online"></span>
                        </div>
                        <div class="balance-card">
                            <span>{{ __('landing.hero.cash') }}</span>
                            <strong>৳ ২৪,৮৫০</strong>
                            <div class="mini-chart" aria-hidden="true">
                                <i style="height:32%"></i><i style="height:52%"></i><i style="height:44%"></i><i style="height:72%"></i><i style="height:64%"></i><i style="height:88%"></i><i style="height:78%"></i>
                            </div>
                        </div>
                        <div class="balance-split">
                            <div class="metric metric-green">
                                <span>{{ __('landing.hero.receivable') }}</span>
                                <strong>৳ ৩৮,৪০০</strong>
                            </div>
                            <div class="metric metric-red">
                                <span>{{ __('landing.hero.payable') }}</span>
                                <strong>৳ ১২,৬৫০</strong>
                            </div>
                        </div>
                        <div class="recent-head">
                            <strong>{{ __('landing.hero.recent') }}</strong>
                            <span>{{ $locale === 'bn' ? 'আজ' : 'Today' }}</span>
                        </div>
                        <div class="activity-row">
                            <span class="activity-icon sale-icon"><x-landing-icon name="receipt" size="17"/></span>
                            <span><strong>{{ __('landing.hero.sale') }}</strong><small>মদিনা স্টোর</small></span>
                            <b class="positive">+ ৳ ২,৪৫০</b>
                        </div>
                        <div class="activity-row">
                            <span class="activity-icon expense-icon"><x-landing-icon name="wallet" size="17"/></span>
                            <span><strong>{{ __('landing.hero.expense') }}</strong><small>দোকান ভাড়া</small></span>
                            <b class="negative">− ৳ ১,২০০</b>
                        </div>
                    </div>

                    <div class="secure-badge">
                        <span><x-landing-icon name="shield" size="21"/></span>
                        <div><strong>{{ __('landing.hero.sync') }}</strong><small>{{ $locale === 'bn' ? 'সর্বশেষ ব্যাকআপ: এইমাত্র' : 'Last backup: just now' }}</small></div>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['stats'])
        <section class="stats-bar" aria-label="Platform highlights">
            <div class="container stats-grid">
                @foreach ($stats as $stat)
                    <div class="stat reveal">
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ $stat['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        @if ($sectionVisibility['pillars'])
        <section class="section section-soft" id="overview">
            <div class="container">
                <div class="section-heading centered reveal">
                    <span class="eyebrow">{{ __('landing.pillars.eyebrow') }}</span>
                    <h2>{{ __('landing.pillars.title') }}</h2>
                    <p>{{ __('landing.pillars.description') }}</p>
                </div>
                <div class="pillar-grid">
                    @foreach ($pillarItems as $index => $item)
                        <article class="pillar-card reveal">
                            <span class="pillar-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="icon-box"><x-landing-icon :name="$item['icon']" size="28"/></span>
                            <h3>{{ $item['title_text'] }}</h3>
                            <p>{{ $item['description_text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['features'])
        <section class="section" id="features">
            <div class="container">
                <div class="section-heading split-heading reveal">
                    <div>
                        <span class="eyebrow">{{ __('landing.features_section.eyebrow') }}</span>
                        <h2>{{ __('landing.features_section.title') }}</h2>
                    </div>
                    <p>{{ __('landing.features_section.description') }}</p>
                </div>
                <div class="feature-grid">
                    @foreach ($features as $index => $feature)
                        <article class="feature-card {{ $index === 0 || $index === 5 ? 'feature-card-wide' : '' }} reveal">
                            <span class="feature-icon"><x-landing-icon :name="$feature['icon']" size="25"/></span>
                            <h3>{{ $feature['title_text'] ?? __($feature['title']) }}</h3>
                            <p>{{ $feature['description_text'] ?? __($feature['description']) }}</p>
                            @if ($index === 0)
                                <div class="ledger-preview" aria-hidden="true">
                                    <div><span class="avatar avatar-green">আ</span><p><b>আলম ট্রেডার্স</b><small>আজ, ১০:৩০</small></p><strong class="positive">পাবেন ৳ ৮,৪০০</strong></div>
                                    <div><span class="avatar avatar-orange">র</span><p><b>রানা স্টোর</b><small>গতকাল</small></p><strong class="negative">দেবেন ৳ ২,২৫০</strong></div>
                                </div>
                            @endif
                            @if ($index === 5)
                                <div class="reminder-preview" aria-hidden="true">
                                    <span class="reminder-date"><b>২৫</b><small>জুলাই</small></span>
                                    <span><b>তাগাদা পাঠান</b><small>৩ জনের পেমেন্ট বাকি</small></span>
                                    <i><x-landing-icon name="bell" size="18"/></i>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['ledger'])
        <section class="section showcase-section section-green">
            <div class="container showcase-grid">
                <div class="showcase-copy reveal">
                    <span class="eyebrow eyebrow-light">{{ __('landing.showcase.ledger.eyebrow') }}</span>
                    <h2>{{ __('landing.showcase.ledger.title') }}</h2>
                    <p>{{ __('landing.showcase.ledger.description') }}</p>
                    <ul class="check-list">
                        @foreach (__('landing.showcase.ledger.points') as $point)
                            <li><span><x-landing-icon name="check" size="17"/></span>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a class="text-link text-link-light" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">{{ __('landing.showcase.cta') }}<x-landing-icon name="arrow" size="18"/></a>
                </div>
                <div class="statement-visual reveal reveal-delay">
                    <div class="statement-window">
                        <div class="statement-toolbar">
                            <span><i></i><i></i><i></i></span>
                            <b>{{ $locale === 'bn' ? 'কাস্টমার স্টেটমেন্ট' : 'Customer statement' }}</b>
                            <em>PDF</em>
                        </div>
                        <div class="statement-person">
                            <span class="avatar avatar-large">ম</span>
                            <div><strong>মদিনা স্টোর</strong><small>০১৭XX-XXXXXX</small></div>
                            <p><small>{{ __('landing.hero.receivable') }}</small><b>৳ ১২,৮৫০</b></p>
                        </div>
                        <div class="statement-line">
                            <span class="line-dot line-dot-green"></span><p><b>{{ __('landing.hero.paid') }}</b><small>২৪ জুলাই ২০২৬ · নগদ</small></p><strong class="positive">− ৳ ২,০০০</strong>
                        </div>
                        <div class="statement-line">
                            <span class="line-dot line-dot-red"></span><p><b>{{ __('landing.hero.sale') }}</b><small>২২ জুলাই ২০২৬ · ৪টি পণ্য</small></p><strong class="negative">+ ৳ ৪,৩৫০</strong>
                        </div>
                        <div class="statement-line">
                            <span class="line-dot line-dot-green"></span><p><b>{{ __('landing.hero.paid') }}</b><small>১৮ জুলাই ২০২৬ · ব্যাংক</small></p><strong class="positive">− ৳ ৫,০০০</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['categories'])
        <section class="section" id="solutions">
            <div class="container">
                <div class="section-heading centered reveal">
                    <span class="eyebrow">{{ __('landing.categories_section.eyebrow') }}</span>
                    <h2>{{ __('landing.categories_section.title') }}</h2>
                    <p>{{ __('landing.categories_section.description') }}</p>
                </div>
                <div class="category-grid">
                    @foreach ($categories as $category)
                        <article class="category-card reveal">
                            <span><x-landing-icon :name="$category['icon']" size="25"/></span>
                            <strong>{{ $category['label_text'] ?? __($category['label']) }}</strong>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['personal'])
        <section class="section section-soft">
            <div class="container showcase-grid showcase-grid-reverse">
                <div class="showcase-copy dark-copy reveal">
                    <span class="eyebrow">{{ __('landing.showcase.personal.eyebrow') }}</span>
                    <h2>{{ __('landing.showcase.personal.title') }}</h2>
                    <p>{{ __('landing.showcase.personal.description') }}</p>
                    <ul class="check-list check-list-dark">
                        @foreach (__('landing.showcase.personal.points') as $point)
                            <li><span><x-landing-icon name="check" size="17"/></span>{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="adaptive-visual reveal reveal-delay">
                    <div class="adaptive-phone">
                        <div class="phone-status"><span>9:41</span><i></i></div>
                        <div class="phone-brand"><img src="{{ asset('images/landing/app-mark.png') }}" alt=""><b>ই-টালি-খাতা</b><span>•••</span></div>
                        <div class="khata-select"><small>{{ $locale === 'bn' ? 'বর্তমান খাতা' : 'Current khata' }}</small><strong>{{ $locale === 'bn' ? 'আমার ব্যবসা' : 'My business' }}⌄</strong></div>
                        <div class="phone-balance"><small>{{ __('landing.hero.cash') }}</small><strong>৳ ২৪,৮৫০</strong><span>+৮.৪%</span></div>
                        <div class="phone-actions">
                            <span><i class="action-in">↓</i><small>{{ $locale === 'bn' ? 'আয়' : 'Income' }}</small></span>
                            <span><i class="action-out">↑</i><small>{{ $locale === 'bn' ? 'খরচ' : 'Expense' }}</small></span>
                            <span><i class="action-note">＋</i><small>{{ $locale === 'bn' ? 'নতুন' : 'New' }}</small></span>
                        </div>
                        <div class="phone-grid">
                            @foreach ([
                                ['wallet', $locale === 'bn' ? 'ক্যাশবুক' : 'Cashbook'],
                                ['users', $locale === 'bn' ? 'কাস্টমার' : 'Customers'],
                                ['package', $locale === 'bn' ? 'স্টক' : 'Stock'],
                                ['chart', $locale === 'bn' ? 'রিপোর্ট' : 'Reports'],
                            ] as [$icon, $label])
                                <span><i><x-landing-icon :name="$icon" size="20"/></i><small>{{ $label }}</small></span>
                            @endforeach
                        </div>
                    </div>
                    <div class="adaptive-chip chip-one"><x-landing-icon name="home" size="20"/>{{ __('landing.categories.homemaker') }}</div>
                    <div class="adaptive-chip chip-two"><x-landing-icon name="education" size="20"/>{{ __('landing.categories.teacher') }}</div>
                    <div class="adaptive-chip chip-three"><x-landing-icon name="building" size="20"/>{{ __('landing.categories.landlord') }}</div>
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['steps'])
        <section class="section steps-section">
            <div class="container">
                <div class="section-heading centered reveal">
                    <span class="eyebrow">{{ __('landing.steps.eyebrow') }}</span>
                    <h2>{{ __('landing.steps.title') }}</h2>
                </div>
                <div class="steps-grid">
                    @foreach ($stepItems as $index => $step)
                        <article class="step reveal">
                            <span>{{ $locale === 'bn' ? strtr((string) ($index + 1), ['1' => '১', '2' => '২', '3' => '৩']) : $index + 1 }}</span>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['backup'])
        <section class="section backup-section">
            <div class="container backup-card">
                <div class="backup-visual reveal">
                    <div class="orbit orbit-one"></div>
                    <div class="orbit orbit-two"></div>
                    <span class="cloud-core"><x-landing-icon name="cloud" size="52"/></span>
                    <span class="orbit-icon orbit-phone"><x-landing-icon name="laptop" size="24"/></span>
                    <span class="orbit-icon orbit-lock"><x-landing-icon name="lock" size="24"/></span>
                    <span class="orbit-icon orbit-check"><x-landing-icon name="check" size="24"/></span>
                </div>
                <div class="showcase-copy dark-copy reveal reveal-delay">
                    <span class="eyebrow">{{ __('landing.showcase.backup.eyebrow') }}</span>
                    <h2>{{ __('landing.showcase.backup.title') }}</h2>
                    <p>{{ __('landing.showcase.backup.description') }}</p>
                    <ul class="check-list check-list-dark">
                        @foreach (__('landing.showcase.backup.points') as $point)
                            <li><span><x-landing-icon name="check" size="17"/></span>{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['pricing'])
        <section class="section section-soft" id="pricing">
            <div class="container">
                <div class="section-heading centered reveal">
                    <span class="eyebrow">{{ __('landing.pricing.eyebrow') }}</span>
                    <h2>{{ __('landing.pricing.title') }}</h2>
                    <p>{{ __('landing.pricing.description') }}</p>
                </div>
                <div class="pricing-grid pricing-grid-{{ min(count($packages), 3) }}">
                    @foreach ($packages as $package)
                        @php
                            $price = number_format($package['price'], $package['price'] == floor($package['price']) ? 0 : 2);
                            if ($locale === 'bn') {
                                $price = strtr($price, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
                            }
                            $days = (string) $package['duration_days'];
                            if ($locale === 'bn') {
                                $days = strtr($days, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
                            }
                        @endphp
                        <article class="price-card {{ $package['featured'] ? 'featured' : '' }} reveal">
                            @if ($package['featured'])
                                <span class="popular-badge">{{ __('landing.pricing.popular') }}</span>
                            @endif
                            <h3>{{ $package['name'] }}</h3>
                            <p>{{ $package['description'] }}</p>
                            <div class="price">
                                @if ($package['price'] > 0)
                                    <sup>{{ __('landing.pricing.currency') }}</sup><strong>{{ $price }}</strong>
                                    <small>{{ $package['duration_days'] >= 365 ? __('landing.pricing.per_year') : __('landing.pricing.per_month') }}</small>
                                @else
                                    <strong>{{ __('landing.pricing.free') }}</strong>
                                    <small>{{ __('landing.pricing.for_days', ['days' => $days]) }}</small>
                                @endif
                            </div>
                            <ul>
                                @foreach ($package['features'] as $feature)
                                    <li><span><x-landing-icon name="check" size="16"/></span>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a class="button {{ $package['featured'] ? 'button-primary' : 'button-secondary' }}" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">{{ __('landing.pricing.choose') }}</a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['faq'])
        <section class="section" id="faq">
            <div class="container faq-layout">
                <div class="section-heading reveal">
                    <span class="eyebrow">{{ __('landing.faq.eyebrow') }}</span>
                    <h2>{{ __('landing.faq.title') }}</h2>
                    <div class="faq-help">
                        <span><x-landing-icon name="community" size="25"/></span>
                        <div>
                            <small>{{ $locale === 'bn' ? 'আরও সাহায্য প্রয়োজন?' : 'Need more help?' }}</small>
                            <a href="mailto:{{ $siteSettings['support_email'] }}">{{ $siteSettings['support_email'] }}</a>
                        </div>
                    </div>
                </div>
                <div class="faq-list reveal reveal-delay">
                    @foreach ($faqs as $index => $faq)
                        <details {{ $index === 0 ? 'open' : '' }}>
                            <summary>{{ $faq['question_text'] ?? __($faq['question']) }}<span></span></summary>
                            <div><p>{{ $faq['answer_text'] ?? __($faq['answer']) }}</p></div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if ($sectionVisibility['cta'])
        <section class="download-section">
            <div class="container">
                <div class="download-card reveal">
                    <div class="download-copy">
                        <span class="eyebrow eyebrow-light">{{ __('landing.cta.eyebrow') }}</span>
                        <h2>{{ __('landing.cta.title') }}</h2>
                        <p>{{ __('landing.cta.description') }}</p>
                        <a class="button button-white button-large" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">
                            <x-landing-icon name="download" size="20"/>
                            {{ __('landing.cta.button') }}
                        </a>
                    </div>
                    <div class="download-art" aria-hidden="true">
                        <div class="download-phone">
                            <img src="{{ asset('images/landing/app-mark.png') }}" alt="">
                            <strong>ই-টালি-খাতা</strong>
                            <small>{{ __('landing.brand_tagline') }}</small>
                            <span><i></i></span>
                        </div>
                        <div class="download-ring ring-one"></div>
                        <div class="download-ring ring-two"></div>
                    </div>
                </div>
            </div>
        </section>
        @endif
    </main>

    @if ($sectionVisibility['global'])
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a class="brand brand-light" href="#home">
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
                <a href="#features">{{ __('landing.nav.features') }}</a>
                @endif
                @if ($sectionVisibility['categories'])
                <a href="#solutions">{{ __('landing.nav.solutions') }}</a>
                @endif
                @if ($sectionVisibility['pricing'])
                <a href="#pricing">{{ __('landing.nav.pricing') }}</a>
                @endif
                @if ($sectionVisibility['faq'])
                <a href="#faq">{{ __('landing.nav.faq') }}</a>
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
                <a href="{{ route('home', ['lang' => $locale === 'bn' ? 'en' : 'bn']) }}">{{ $locale === 'bn' ? 'English' : 'বাংলা' }}</a>
                <a href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener">{{ __('landing.nav.download') }}</a>
            </div>
        </div>
        <div class="container footer-bottom">
            <span>© {{ now()->year }} E-Tali-Khata. {{ __('landing.footer.rights') }}</span>
            <span>Developed By <a href="https://creativeitbari.com/" target="_blank" rel="noopener">CreativeITbari</a></span>
        </div>
    </footer>
    @endif

    <a class="floating-download" href="{{ $siteSettings['android_url'] }}" target="_blank" rel="noopener" aria-label="{{ __('landing.nav.download') }}">
        <x-landing-icon name="download" size="22"/>
        <span>{{ __('landing.nav.download') }}</span>
    </a>

    <script src="{{ asset('js/landing.js') }}" defer></script>
</body>
</html>
