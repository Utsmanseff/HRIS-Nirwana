@props(['active' => ''])
@php
    // Bottom-nav 4 slot tetap karyawan (mockup m-home): Beranda/Riwayat/Notif/Profil.
    // Beranda/Riwayat/Notif via NavMenu::href (Route::has-guarded).
    $reg = collect(\App\Support\NavMenu::untuk(auth()->user()))->keyBy('id');
    $belumDibaca = auth()->user()->unreadNotifications()->count();
    $nav = [
        ['id' => 'beranda', 'label' => 'Beranda', 'href' => isset($reg['beranda']) ? \App\Support\NavMenu::href($reg['beranda']) : '#', 'icon' => 'home'],
        ['id' => 'riwayat', 'label' => 'Riwayat', 'href' => isset($reg['riwayat']) ? \App\Support\NavMenu::href($reg['riwayat']) : '#', 'icon' => 'history'],
        ['id' => 'notif', 'label' => 'Notifikasi', 'href' => isset($reg['notif']) ? \App\Support\NavMenu::href($reg['notif']) : '#', 'icon' => 'bell', 'badge' => $belumDibaca],
        ['id' => 'profil', 'label' => 'Profil', 'href' => route('profil'), 'icon' => 'user'],
    ];
@endphp
<nav class="m-nav">
    @foreach ($nav as $it)
        <a href="{{ $it['href'] }}"
           @class(['mnav-item', 'on' => $it['id'] === $active, 'opacity-40 pointer-events-none' => $it['href'] === '#'])>
            <span class="relative grid place-items-center">
                <x-icon :name="$it['icon']" :size="23" stroke-width="1.9" />
                @if (!empty($it['badge']))
                    <span class="absolute -top-1.5 -right-2 min-w-[15px] h-[15px] px-1 rounded-full text-white text-[9px] font-bold grid place-items-center" style="background:var(--danger-500)">{{ $it['badge'] > 9 ? '9+' : $it['badge'] }}</span>
                @endif
            </span>
            <span class="text-[10.5px] font-semibold mt-0.5">{{ $it['label'] }}</span>
        </a>
    @endforeach
</nav>
