@props(['name', 'size' => 20])
{{-- Einfache Liniensymbole, Farbe über currentColor --}}
<svg {{ $attributes->merge(['class' => 'icon icon--'.$name]) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('tree')
            <path d="M12 3c-3.3 0-6 2.6-6 5.9 0 1.2.4 2.3 1 3.2A4.5 4.5 0 0 0 10.5 19H12m0-16c3.3 0 6 2.6 6 5.9 0 1.2-.4 2.3-1 3.2A4.5 4.5 0 0 1 13.5 19H12m0-16v18"/>
            @break
        @case('bunker')
            <path d="M3 15c2.5-4 6-6 9-6s6.5 2 9 6c-3 2-6 3-9 3s-6-1-9-3Z"/><path d="M8 14h.01M12 13h.01M16 14h.01"/>
            @break
        @case('water')
            <path d="M3 9c1.5 1.2 3 1.2 4.5 0s3-1.2 4.5 0 3 1.2 4.5 0 3-1.2 4.5 0M3 15c1.5 1.2 3 1.2 4.5 0s3-1.2 4.5 0 3 1.2 4.5 0 3-1.2 4.5 0"/>
            @break
        @case('oob')
            <path d="M5 4v16M19 4v16M5 8h14M5 16h14"/>
            @break
        @case('flag')
            <path d="M7 21V3l10 4-10 4"/><path d="M4 21h8"/>
            @break
        @case('arrow-left')
            <path d="M15 5l-7 7 7 7"/>
            @break
        @case('arrow-right')
            <path d="M9 5l7 7-7 7"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>
            @break
        @case('expand')
            <path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>
            @break
        @case('heart')
            <path d="M12 20s-7.5-4.6-7.5-10A4.3 4.3 0 0 1 12 7.4 4.3 4.3 0 0 1 19.5 10c0 5.4-7.5 10-7.5 10Z" fill="none"/>
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5"/>
            @break
        @case('video')
            <rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10.5 5-3v9l-5-3"/>
            @break
        @case('sphere')
            <circle cx="12" cy="12" r="8.5"/><ellipse cx="12" cy="12" rx="3.6" ry="8.5"/><path d="M3.5 12h17"/>
            @break
        @case('plan')
            <path d="M9 4 3.5 6v14L9 18l6 2 5.5-2V4L15 6 9 4Z"/><path d="M9 4v14M15 6v14"/>
            @break
        @case('user')
            <circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20c1.3-3.6 4.2-5.5 7.5-5.5s6.2 1.9 7.5 5.5"/>
            @break
        @case('ruler')
            <path d="m4 15.5 11.5-11.5 4.5 4.5L8.5 20 4 15.5Z"/><path d="m8 11.5 2 2M10.5 9l1.5 1.5M13 6.5l2 2"/>
            @break
        @case('home')
            <path d="M4 11 12 4l8 7"/><path d="M6 9.5V20h12V9.5"/><path d="M10 20v-5h4v5"/>
            @break
        @case('gear')
            <circle cx="12" cy="12" r="3"/><path d="M12 3v2.5M12 18.5V21M3 12h2.5M18.5 12H21M5.6 5.6l1.8 1.8M16.6 16.6l1.8 1.8M5.6 18.4l1.8-1.8M16.6 7.4l1.8-1.8"/>
            @break
        @case('logout')
            <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4M6 12h10"/>
            @break
        @case('help')
            <circle cx="12" cy="12" r="8.5"/><path d="M9.7 9.5a2.4 2.4 0 1 1 3.4 2.2c-.7.3-1.1.9-1.1 1.6v.4"/><path d="M12 16.8h.01"/>
            @break
        @case('shield')
            <path d="M12 3 5 6v5c0 4.4 3 8.3 7 9.5 4-1.2 7-5.1 7-9.5V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>
            @break
        @case('bell')
            <path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 1.5h-15L6 16.5Z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>
            @break
        @case('mail')
            <rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>
            @break
        @case('pin')
            <path d="M12 21s-6.5-6.1-6.5-11a6.5 6.5 0 0 1 13 0c0 4.9-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>
            @break
        @default
            <circle cx="12" cy="12" r="3.5"/><circle cx="12" cy="12" r="8.5"/>
    @endswitch
</svg>
