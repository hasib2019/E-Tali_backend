@php
    $bn = ($p['lang'] ?? 'bn') === 'bn';
    $L = $bn
        ? ['title' => 'হিসাবের বিবরণ', 'to' => 'প্রাপক', 'opening' => 'শুরুর বাকি', 'closing' => 'বর্তমান বাকি', 'date' => 'তারিখ', 'detail' => 'বিবরণ', 'gave' => 'দিলাম', 'got' => 'পেলাম', 'balance' => 'বাকি', 'youOwe' => 'আপনার কাছে পাওনা', 'weOwe' => 'আমাদের কাছে পাবেন', 'settled' => 'পরিশোধিত', 'note' => 'এই পাতাটি শুধু দেখার জন্য।', 'empty' => 'কোনো লেনদেন নেই']
        : ['title' => 'Account statement', 'to' => 'For', 'opening' => 'Opening balance', 'closing' => 'Current balance', 'date' => 'Date', 'detail' => 'Details', 'gave' => 'Given', 'got' => 'Received', 'balance' => 'Balance', 'youOwe' => 'You owe', 'weOwe' => 'We owe you', 'settled' => 'Settled', 'note' => 'This page is read-only.', 'empty' => 'No entries'];
    $fmt = fn ($n) => '৳'.number_format(abs((float) $n), 2);
    $closing = (float) ($p['closing_balance'] ?? 0);
@endphp
<!DOCTYPE html>
<html lang="{{ $bn ? 'bn' : 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $L['title'] }} — {{ $s->business_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #132a33; --muted: #5a7079; --line: #e3ecea; --brand: #087a5b; --give: #c81e1e; --get: #0e9f6e; --bg: #f4f8f7; --card: #fff; }
        @media (prefers-color-scheme: dark) { :root { --ink: #e7efed; --muted: #9db2ad; --line: #2a3a37; --brand: #34d399; --give: #f98080; --get: #31c48d; --bg: #0f1a18; --card: #16241f; } }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: 'Hind Siliguri', -apple-system, Roboto, sans-serif; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 20px 16px 40px; }
        .head { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 18px; }
        .shop { font-size: 20px; font-weight: 700; }
        .muted { color: var(--muted); font-size: 14px; }
        .bal { margin-top: 14px; display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
        .bal .v { font-size: 30px; font-weight: 700; }
        .give { color: var(--give); } .get { color: var(--get); }
        .table { margin-top: 16px; background: var(--card); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
        .row { display: grid; grid-template-columns: 1fr auto; gap: 4px 12px; padding: 12px 16px; border-bottom: 1px solid var(--line); }
        .row:last-child { border-bottom: 0; }
        .row .t { font-weight: 600; }
        .row .amt { text-align: right; font-weight: 700; }
        .row .rb { text-align: right; font-size: 13px; color: var(--muted); }
        .foot { margin-top: 18px; text-align: center; color: var(--muted); font-size: 12px; }
    </style>
</head>
<body>
<main class="wrap">
    <section class="head">
        <div class="shop">{{ $s->business_name }}</div>
        @if (! empty($p['business_phone']))<div class="muted">{{ $p['business_phone'] }}</div>@endif
        <div class="muted" style="margin-top:8px">{{ $L['title'] }} · {{ $L['to'] }}: <strong>{{ $s->party_name }}</strong></div>
        <div class="bal">
            <div>
                <div class="muted">{{ $closing > 0 ? $L['youOwe'] : ($closing < 0 ? $L['weOwe'] : $L['settled']) }}</div>
                <div class="v {{ $closing > 0 ? 'give' : 'get' }}">{{ $fmt($closing) }}</div>
            </div>
            <div class="muted">{{ $L['opening'] }}: {{ $fmt($p['opening_balance'] ?? 0) }}</div>
        </div>
    </section>

    <section class="table">
        @forelse (array_reverse($p['entries'] ?? []) as $e)
            <div class="row">
                <div>
                    <div class="t">{{ $e['title'] }}</div>
                    <div class="muted">{{ \Illuminate\Support\Carbon::parse($e['date'])->format('d M Y') }}</div>
                </div>
                <div>
                    @if ((float) $e['gave'] > 0)<div class="amt give">{{ $L['gave'] }} {{ $fmt($e['gave']) }}</div>@endif
                    @if ((float) $e['got'] > 0)<div class="amt get">{{ $L['got'] }} {{ $fmt($e['got']) }}</div>@endif
                    <div class="rb">{{ $L['balance'] }} {{ $fmt($e['balance']) }}</div>
                </div>
            </div>
        @empty
            <div class="row"><div class="muted">{{ $L['empty'] }}</div></div>
        @endforelse
    </section>

    <p class="foot">{{ $L['note'] }} · {{ \Illuminate\Support\Carbon::parse($p['generated_at'])->format('d M Y, h:i A') }} · E-Tali-Khata</p>
</main>
</body>
</html>
