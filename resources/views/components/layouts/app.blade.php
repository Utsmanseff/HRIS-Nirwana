@props(['title' => '', 'active' => '', 'breadcrumb' => []])
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
<body data-active="{{ $active }}">
    <div class="app-shell">
        <x-shell.sidebar :active="$active" />
        <div class="min-w-0 flex flex-col">
            <x-shell.topbar :title="$title" :breadcrumb="$breadcrumb" />
            <main class="content w-full">
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
