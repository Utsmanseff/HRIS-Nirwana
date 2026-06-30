@props(['title' => ''])
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
<body class="min-h-screen grid place-items-center p-4" style="background:var(--bg-app)">
    <div class="w-full max-w-sm">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
