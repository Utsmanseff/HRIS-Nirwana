@props(['title' => '', 'active' => '', 'brand' => false, 'back' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' · ' : '' }}{{ config('app.name', 'Nirwana HRIS') }}</title>

    @include('partials.theme-init')
    @include('partials.pwa-head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="is-mobile-shell">
    <div class="phone">
        <x-shell.appbar :title="$title" :brand="$brand" :back="$back" />
        <main class="m-main">
            {{ $slot }}
        </main>
        <x-shell.bottom-nav :active="$active" />
    </div>
    @livewireScripts
</body>
</html>
