@props(['name' => '', 'size' => 20])
@php
    // Stroke-based line icons (24x24). Ported from docs/mockups shell.js / mobile.js.
    $icons = [
        // sidebar
        'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users'  => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 6.2a3 3 0 0 1 0 5.6M21 19a5 5 0 0 0-4-4.9"/>',
        'tree'   => '<rect x="9" y="3" width="6" height="4" rx="1"/><rect x="3" y="15" width="6" height="4" rx="1"/><rect x="15" y="15" width="6" height="4" rx="1"/><path d="M12 7v4M6 15v-2h12v2"/>',
        'badge'  => '<path d="M12 3l2.5 1.6L17.5 4l.9 3 2.6 1.6L20 12l1 2.4-2.6 1.6-.9 3-3-.6L12 21l-2.5-1.6-3 .6-.9-3L3 14.4 4 12 3 9.6 5.6 8l.9-3 3 .6z"/>',
        'doc'    => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 7a2 2 0 0 1 2-2h7l5 5v9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z"/><path d="M9 13h6M9 17h4"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'ticket' => '<path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4z"/><path d="M14 7v10"/>',
        'box'    => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>',
        'gavel'  => '<path d="M14 5l5 5M11 8l5 5M5 19l6-6M9.5 6.5l4 4"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'cog'    => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        // mobile bottom nav
        'home'   => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7M3 4v4h4"/><path d="M12 8v4l3 2"/>',
        'bell'   => '<path d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>',
        // ui
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>',
        'menu'   => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'back'   => '<path d="M15 18l-6-6 6-6"/>',
        'chevron-updown' => '<path d="M8 9l4-4 4 4M8 15l4 4 4-4"/>',
        'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'   => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
    ];
    $path = $icons[$name] ?? '';
@endphp
<svg {{ $attributes->merge(['class' => '']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
