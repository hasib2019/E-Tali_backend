@php
    $bn = ($d['lang'] ?? 'bn') === 'bn';
    $L = $bn
        ? ['call' => 'কল করুন', 'whatsapp' => 'হোয়াটসঅ্যাপ', 'email' => 'ইমেইল', 'map' => 'ম্যাপে দেখুন', 'save' => 'কন্টাক্ট সেভ করুন', 'site' => 'ওয়েবসাইট', 'made' => 'টালি খাতা দিয়ে তৈরি']
        : ['call' => 'Call', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'map' => 'Open in maps', 'save' => 'Save contact', 'site' => 'Website', 'made' => 'Made with Tali Khata'];
    $phone = $d['phone'] ?? null;
    // WhatsApp wants international digits only; local BD numbers (01…) get 88.
    $wa = $phone ? preg_replace('/\D+/', '', $phone) : null;
    if ($wa && str_starts_with($wa, '01')) {
        $wa = '88'.$wa;
    }
    $site = $d['website'] ?? null;
    $siteUrl = $site ? (preg_match('#^https?://#i', $site) ? $site : 'https://'.$site) : null;
    $title = trim($d['name'].(! empty($d['business_name']) ? ' — '.$d['business_name'] : ''));
    $summary = collect([$d['designation'] ?? null, $phone, $d['address'] ?? null])->filter()->implode(' · ');
    $initial = mb_substr(trim($d['business_name'] ?: $d['name']), 0, 1);
@endphp
<!DOCTYPE html>
<html lang="{{ $bn ? 'bn' : 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $summary }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $summary }}">
    <meta property="og:url" content="{{ route('business-card.show', $card->token) }}">
    @if ($render)
        <meta property="og:image" content="{{ $render }}">
        <meta property="og:image:width" content="1050">
        <meta property="og:image:height" content="600">
        <meta name="twitter:card" content="summary_large_image">
    @elseif ($photo)
        <meta property="og:image" content="{{ $photo }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --card-bg: {{ $c['bg'] }}; --card-ink: {{ $c['ink'] }}; --card-muted: {{ $c['muted'] }}; --card-accent: {{ $c['accent'] }};
            --page: #F4F1EC; --ink: #1F1B16; --muted: #6B625A; --line: #E3DDD3; --surface: #FFFFFF;
        }
        @media (prefers-color-scheme: dark) {
            :root { --page: #121010; --ink: #F4F0E9; --muted: #B3A99C; --line: #2E2823; --surface: #1F1B17; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--page); color: var(--ink); font-family: 'Hind Siliguri', -apple-system, Roboto, sans-serif; line-height: 1.55; }
        .wrap { max-width: 520px; margin: 0 auto; padding: 24px 16px 40px; display: grid; gap: 16px; }
        .render { width: 100%; aspect-ratio: 1050 / 600; border-radius: 14px; display: block; box-shadow: 0 10px 30px rgba(0,0,0,.18); object-fit: cover; }
        /* Fallback card when the app sent no rendered image. */
        .card { aspect-ratio: 1050 / 600; border-radius: 14px; background: var(--card-bg); color: var(--card-ink); padding: 22px; display: flex; gap: 16px; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,.18); border-top: 6px solid var(--card-accent); }
        .avatar { width: 72px; height: 72px; border-radius: 50%; flex: none; display: grid; place-items: center; font-size: 30px; font-weight: 700; background: var(--card-accent); color: #fff; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .who { min-width: 0; }
        .name { font-size: 22px; font-weight: 700; margin: 0; }
        .role { color: var(--card-muted); font-size: 14px; }
        .biz { color: var(--card-accent); font-weight: 700; font-size: 15px; margin-top: 4px; }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; min-height: 50px; padding: 0 12px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 15px; border: 1px solid var(--line); background: var(--surface); color: var(--ink); }
        .btn svg { width: 18px; height: 18px; flex: none; }
        .btn.primary { grid-column: 1 / -1; background: #DDF3E8; border-color: #A7DFC8; color: #07684A; }
        @media (prefers-color-scheme: dark) { .btn.primary { background: #122E26; border-color: #1F4B3E; color: #A7F3D0; } }
        .details { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 6px 16px; }
        .row { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); }
        .row:last-child { border-bottom: 0; }
        .row .k { color: var(--muted); font-size: 13px; min-width: 70px; }
        .row .v { font-weight: 500; word-break: break-word; }
        .foot { text-align: center; color: var(--muted); font-size: 12px; }
    </style>
</head>
<body>
<main class="wrap">
    @if ($render)
        <img class="render" src="{{ $render }}" alt="{{ $title }}">
    @else
        <section class="card">
            <div class="avatar">@if ($photo)<img src="{{ $photo }}" alt="">@else{{ $initial }}@endif</div>
            <div class="who">
                <h1 class="name">{{ $d['name'] }}</h1>
                @if (! empty($d['designation']))<div class="role">{{ $d['designation'] }}</div>@endif
                @if (! empty($d['business_name']))<div class="biz">{{ $d['business_name'] }}</div>@endif
            </div>
        </section>
    @endif

    <section class="actions">
        <a class="btn primary" href="{{ route('business-card.vcard', $card->token) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            {{ $L['save'] }}
        </a>
        @if ($phone)
            <a class="btn" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
                {{ $L['call'] }}
            </a>
            <a class="btn" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.2-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg>
                {{ $L['whatsapp'] }}
            </a>
        @endif
        @if (! empty($d['email']))
            <a class="btn" href="mailto:{{ $d['email'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                {{ $L['email'] }}
            </a>
        @endif
        @if (! empty($d['address']))
            <a class="btn" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($d['address']) }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s7-6.3 7-12a7 7 0 1 0-14 0c0 5.7 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg>
                {{ $L['map'] }}
            </a>
        @endif
        @if ($siteUrl)
            <a class="btn" href="{{ $siteUrl }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
                {{ $L['site'] }}
            </a>
        @endif
    </section>

    <section class="details">
        <div class="row"><div class="v" style="font-size:18px;font-weight:700">{{ $d['name'] }}</div></div>
        @foreach ([
            [$bn ? 'পদবি' : 'Title', $d['designation'] ?? null],
            [$bn ? 'প্রতিষ্ঠান' : 'Business', $d['business_name'] ?? null],
            [$bn ? 'মোবাইল' : 'Mobile', $phone],
            [$bn ? 'ইমেইল' : 'Email', $d['email'] ?? null],
            [$bn ? 'ঠিকানা' : 'Address', $d['address'] ?? null],
            [$L['site'], $site],
        ] as [$k, $v])
            @if (! empty($v))
                <div class="row"><div class="k">{{ $k }}</div><div class="v">{{ $v }}</div></div>
            @endif
        @endforeach
    </section>

    <div class="foot">{{ $L['made'] }}</div>
</main>
</body>
</html>
