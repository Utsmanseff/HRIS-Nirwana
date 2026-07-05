@props(['active' => ''])
@php
    // Bottom-nav 4 slot tetap karyawan (mockup m-home): Beranda/Riwayat/Notif/Profil.
    // Beranda via NavMenu::href (Route::has-guarded) → '#' sebelum route beranda ada (Task 6).
    $reg = collect(\App\Support\NavMenu::untuk(auth()->user()))->keyBy('id');
    $nav = [
        ['id' => 'beranda', 'label' => 'Beranda', 'href' => isset($reg['beranda']) ? \App\Support\NavMenu::href($reg['beranda']) : '#', 'icon' => 'home'],
        ['id' => 'riwayat', 'label' => 'Riwayat', 'href' => '#', 'icon' => 'history'],
        ['id' => 'notif', 'label' => 'Notifikasi', 'href' => '#', 'icon' => 'bell', 'badge' => 3],
        ['id' => 'profil', 'label' => 'Profil', 'href' => route('profil'), 'icon' => 'user'],
    ];
@endphp
<nav class="m-nav">
    @foreach ($nav as $it)
        <a href="{{ $it['href'] }}"
           @class(['mnav-item', 'on' => $it['id'] === $active, 'opacity-40 pointer-events-none' => $it['href'] === '#'])>
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
