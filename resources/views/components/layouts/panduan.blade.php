@props(['title' => '', 'bab' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title . ' · ' : '' }}Panduan · {{ config('app.name', 'Nirwana HRIS') }}</title>

    @include('partials.theme-init')
    @include('partials.pwa-head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Lompat-anchor tak boleh tersembunyi di balik header sticky setinggi 56px. */
        html { scroll-behavior: smooth; }
        .panduan-isi [id] { scroll-margin-top: 4.5rem; }
    </style>
</head>
<body style="background:var(--bg-app)">
    <header class="sticky top-0 z-30 border-b" style="background:var(--bg-card);border-color:var(--border)">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center gap-3">
            <a href="{{ route('panduan') }}" class="flex items-center gap-2 min-w-0" title="Kembali ke Daftar Isi">
                <x-logo :size="22" />
                <span class="font-extrabold text-[14px] tracking-tight truncate">Panduan NirwanaHRIS</span>
            </a>
            <div class="ml-auto flex items-center gap-1">
                <x-theme-toggle />
                @auth
                    <a href="{{ route('beranda') }}" class="btn btn-sm btn-secondary">Ke Aplikasi</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-primary">Masuk</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="panduan-isi max-w-3xl mx-auto px-4 py-6 pb-16">
        @if ($bab)
            <nav class="mb-4 text-[12px] flex items-center gap-1" style="color:var(--text-muted)">
                <a href="{{ route('panduan') }}" class="hover:underline font-semibold">← Daftar Isi</a>
                <span class="mx-1">/</span>
                <span class="truncate">{{ $bab['judul'] }}</span>
            </nav>
            <h1 id="atas" class="text-2xl font-extrabold tracking-tight mb-1">{{ $bab['judul'] }}</h1>
            <p class="text-[13px] mb-3" style="color:var(--text-muted)">{{ $bab['ringkas'] }}</p>
            <x-panduan.peran :peran="$bab['peran']" />
            <div class="divider my-5"></div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
