@props(['active' => ''])
@php
    // Nav model. href '#' = route not wired yet (filled in per-phase implementation).
    $nav = [
        ['group' => null, 'items' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '#', 'icon' => 'grid'],
        ]],
        ['group' => 'SDM', 'items' => [
            ['id' => 'karyawan', 'label' => 'Karyawan', 'href' => route('sdm.karyawan'), 'icon' => 'users'],
            ['id' => 'struktur', 'label' => 'Struktur Organisasi', 'href' => route('sdm.struktur'), 'icon' => 'tree'],
            ['id' => 'jabatan', 'label' => 'Jabatan & Level', 'href' => route('sdm.jabatan'), 'icon' => 'badge'],
            ['id' => 'kontrak', 'label' => 'Kontrak & Pengingat', 'href' => '#', 'icon' => 'doc'],
            ['id' => 'dokumen', 'label' => 'Dokumen', 'href' => '#', 'icon' => 'folder'],
        ]],
        ['group' => 'Operasional', 'items' => [
            ['id' => 'cuti', 'label' => 'Cuti', 'href' => '#', 'icon' => 'calendar'],
            ['id' => 'disiplin', 'label' => 'Disiplin', 'href' => '#', 'icon' => 'gavel'],
            ['id' => 'tiket', 'label' => 'Ticketing', 'href' => '#', 'icon' => 'ticket'],
            ['id' => 'inventaris', 'label' => 'Inventaris', 'href' => '#', 'icon' => 'box'],
            ['id' => 'absensi', 'label' => 'Absensi', 'href' => '#', 'icon' => 'clock'],
            ['id' => 'jadwal', 'label' => 'Jadwal Shift', 'href' => '#', 'icon' => 'calendar'],
        ]],
        ['group' => 'Sistem', 'items' => [
            ['id' => 'pengguna', 'label' => 'Pengguna & Role', 'href' => route('sistem.pengguna'), 'icon' => 'shield', 'can' => 'kelola-rbac'],
            ['id' => 'pengaturan', 'label' => 'Pengaturan', 'href' => '#', 'icon' => 'cog'],
        ]],
    ];
@endphp
<aside class="sidebar">
    <div class="sb-brand">
        <span class="sb-logo"><x-logo :size="24" /></span>
        <div class="leading-tight sb-label">
            <div class="font-extrabold text-[15px] tracking-tight text-white">Nirwana<span class="text-brand-200">HRIS</span></div>
            <div class="text-[10px] text-brand-200/70 font-semibold uppercase tracking-wider">RSU Nirwana</div>
        </div>
        <button type="button" data-sb-toggle
                class="btn btn-ghost btn-icon ml-auto text-white/70 hover:text-white"
                aria-label="Ciutkan sidebar"
                :aria-label="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
                @click="collapsed = !collapsed;
                        document.documentElement.dataset.sidebar = collapsed ? 'collapsed' : 'expanded';
                        localStorage.setItem('nirwana-sidebar', collapsed ? 'collapsed' : 'expanded')">
            <x-icon name="menu" :size="18" stroke-width="2" />
        </button>
    </div>

    <nav class="py-4 flex-1 overflow-y-auto">
        @foreach ($nav as $group)
            <div class="px-3">
                @if ($group['group'])
                    <div class="nv-group sb-label">{{ $group['group'] }}</div>
                @endif
                <div class="space-y-0.5">
                    @foreach ($group['items'] as $it)
                        @continue(isset($it['can']) && ! auth()->user()?->can($it['can']))
                        <a href="{{ $it['href'] }}" title="{{ $it['label'] }}" class="nv-item {{ $it['id'] === $active ? 'nv-active' : '' }}">
                            <span class="nv-ic"><x-icon :name="$it['icon']" /></span>
                            <span class="flex-1 sb-label">{{ $it['label'] }}</span>
                            @isset($it['badge'])
                                <span class="nv-badge">{{ $it['badge'] }}</span>
                            @endisset
                        </a>
                    @endforeach
                </div>
            </div>
            @if (!$loop->last)
                <div class="my-3"></div>
            @endif
        @endforeach
    </nav>

    <div class="p-3 border-t border-white/10">
        <a href="{{ route('profil') }}" title="Profil Saya" class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
            <span class="avatar w-8 h-8 text-xs" style="background:var(--brand-200);color:var(--brand-800)">
                {{ \Illuminate\Support\Str::of(auth()->user()?->name ?? 'U')->explode(' ')->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
            </span>
            <div class="leading-tight flex-1 min-w-0 sb-label">
                <div class="text-[13px] font-semibold text-white truncate">{{ auth()->user()?->name ?? 'Pengguna' }}</div>
                <div class="text-[11px] text-brand-200/70 truncate">{{ auth()->user()?->getRoleNames()->implode(' · ') ?: 'Karyawan' }}</div>
            </div>
            <svg width="16" class="text-brand-200/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
        </a>
    </div>
</aside>
