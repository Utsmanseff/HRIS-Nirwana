@props(['title' => '', 'active' => '', 'breadcrumb' => [], 'brand' => false, 'back' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>{{ $title ? $title . ' · ' : '' }}{{ config('app.name', 'Nirwana HRIS') }}</title>

    @include('partials.theme-init')
    @include('partials.sidebar-init')
    @include('partials.pwa-head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body data-active="{{ $active }}">
    {{-- Satu layout responsif. .app-shell grid dipertahankan (sistem collapse/hover sidebar
         desktop). Di < lg (shell.css @media max-width:1024px) grid jadi 1 kolom & sidebar
         off-canvas. Chrome ditoggle by breakpoint pakai display:contents agar sticky child
         (topbar/appbar/bottom-nav) tetap sticky relatif ke kolom, bukan ke wrapper. --}}
    <div class="app-shell"
         x-data="{ collapsed: document.documentElement.dataset.sidebar === 'collapsed' }"
         :class="{ 'sb-collapsed': collapsed }">
        {{-- Sidebar: kolom grid di ≥ lg; off-canvas (tersembunyi) di < lg via shell.css --}}
        <x-shell.sidebar :active="$active" />

        <div class="min-w-0 flex flex-col">
            {{-- Chrome atas: topbar (desktop) / appbar (mobile) --}}
            <div class="hidden lg:contents"><x-shell.topbar :title="$title" :breadcrumb="$breadcrumb" /></div>
            <div class="contents lg:hidden"><x-shell.appbar :title="$title" :brand="$brand" :back="$back" /></div>

            {{-- Konten: slot dirender SEKALI (Livewire single root) --}}
            <main class="content w-full flex-1">
                {{ $slot }}
            </main>

            {{-- Bottom-nav: mobile saja, di bawah main --}}
            <div class="contents lg:hidden"><x-shell.bottom-nav :active="$active" /></div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
