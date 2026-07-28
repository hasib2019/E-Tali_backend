@props(['name', 'size' => 24])

<svg
    {{ $attributes->merge(['class' => 'icon']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('users')
        @case('community')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            @break
        @case('wallet')
            <path d="M20 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h15v12H5a3 3 0 0 1-3-3V6"/>
            <path d="M16 13h4"/>
            @break
        @case('receipt')
            <path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2Z"/>
            <path d="M9 7h6M9 11h6M9 15h3"/>
            @break
        @case('package')
            <path d="m21 8-9-5-9 5 9 5 9-5Z"/>
            <path d="m3 8 9 5v9l9-5V8M12 13v9"/>
            @break
        @case('chart')
            <path d="M3 3v18h18"/>
            <path d="m7 16 4-5 3 3 5-7"/>
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
            <path d="M10 21h4"/>
            @break
        @case('cloud')
            <path d="M17.5 19H6a4 4 0 0 1-.6-7.95A7 7 0 0 1 19 9a5 5 0 0 1-1.5 10Z"/>
            <path d="m9 15 3-3 3 3M12 12v7"/>
            @break
        @case('shield')
            <path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"/>
            <path d="m9 12 2 2 4-4"/>
            @break
        @case('store')
            <path d="M3 9l2-6h14l2 6M5 13v8h14v-8"/>
            <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/>
            <path d="M9 21v-6h6v6"/>
            @break
        @case('briefcase')
            <rect x="3" y="7" width="18" height="13" rx="2"/>
            <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2"/>
            @break
        @case('education')
            <path d="m2 10 10-5 10 5-10 5L2 10Z"/>
            <path d="M6 12.5V17c3 2.5 9 2.5 12 0v-4.5M22 10v6"/>
            @break
        @case('home')
            <path d="m3 11 9-8 9 8"/>
            <path d="M5 10v11h14V10M9 21v-7h6v7"/>
            @break
        @case('plane')
            <path d="m22 2-8 20-3-9-9-3 20-8Z"/>
            <path d="M22 2 11 13"/>
            @break
        @case('bike')
            <circle cx="5.5" cy="17.5" r="3.5"/>
            <circle cx="18.5" cy="17.5" r="3.5"/>
            <path d="m5.5 17.5 4-8h4l5 8M9.5 9.5l4 8M7 6h4"/>
            @break
        @case('building')
            <path d="M3 21h18M6 21V3h12v18M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1"/>
            @break
        @case('meal')
            <path d="M6 2v8M3 2v5a3 3 0 0 0 6 0V2M6 10v12M16 2v20M16 2c4 2 5 7 0 10"/>
            @break
        @case('laptop')
            <rect x="4" y="4" width="16" height="12" rx="1"/>
            <path d="M2 20h20M8 20l1-4h6l1 4"/>
            @break
        @case('tools')
            <path d="m14.7 6.3 3-3a4.2 4.2 0 0 1-5.6 5.6L5 16l3 3 7.1-7.1a4.2 4.2 0 0 1 5.6-5.6l-3 3-3-3Z"/>
            <path d="m4 14-2 2 6 6 2-2"/>
            @break
        @case('wifi')
            <path d="M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M2 9a14 14 0 0 1 20 0"/>
            <circle cx="12" cy="20" r="1"/>
            @break
        @case('tv')
            <rect x="3" y="6" width="18" height="14" rx="2"/>
            <path d="m8 2 4 4 4-4"/>
            @break
        @case('book')
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5v14Z"/>
            <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>
            @break
        @case('check')
            <path d="m5 12 4 4L19 6"/>
            @break
        @case('arrow')
            <path d="M5 12h14M13 6l6 6-6 6"/>
            @break
        @case('download')
            <path d="M12 3v12M7 10l5 5 5-5"/>
            <path d="M5 21h14"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18"/>
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
            @break
        @case('lock')
            <rect x="4" y="10" width="16" height="11" rx="2"/>
            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
            @break
        @default
            <circle cx="12" cy="12" r="9"/>
    @endswitch
</svg>
