@props(['active' => ''])
@php
    // href '#' = route wired per-phase. badge optional.
    $nav = [
        ['id' => 'beranda', 'label' => 'Beranda', 'href' => '#', 'icon' => 'home'],
        ['id' => 'riwayat', 'label' => 'Riwayat', 'href' => '#', 'icon' => 'history'],
        ['id' => 'notif', 'label' => 'Notifikasi', 'href' => '#', 'icon' => 'bell', 'badge' => 3],
        ['id' => 'profil', 'label' => 'Profil', 'href' => '#', 'icon' => 'user'],
    ];
@endphp
<nav class="m-nav">
    @foreach ($nav as $it)
        <a href="{{ $it['href'] }}" class="mnav-item {{ $it['id'] === $active ? 'on' : '' }}">
            <span class="relative grid place-items-center">
                <x-icon :name="$it['icon']" :size="23" stroke-width="1.9" />
                @isset($it['badge'])
                    <span class="absolute -top-1.5 -right-2 min-w-[15px] h-[15px] px-1 rounded-full text-white text-[9px] font-bold grid place-items-center" style="background:var(--danger-500)">{{ $it['badge'] }}</span>
                @endisset
            </span>
            <span class="text-[10.5px] font-semibold mt-0.5">{{ $it['label'] }}</span>
        </a>
    @endforeach
</nav>
