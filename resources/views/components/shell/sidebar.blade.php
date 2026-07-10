@props(['active' => ''])
@php
    // Item nav dari registry (source of truth, gate-permission). Dikelompokkan by group.
    $items = \App\Support\NavMenu::untuk(auth()->user());
    $grouped = collect($items)->groupBy(fn ($it) => $it['group'] ?? '')->all();
    $urutanGrup = ['', 'SDM', 'Operasional', 'Sistem'];
    $activeGroup = collect($items)->firstWhere('id', $active)['group'] ?? null;
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

    <nav class="py-4 flex-1 overflow-y-auto"
         x-data="{
            open: JSON.parse(localStorage.getItem('nirwana-sb-groups') || '{}'),
            isOpen(g){ return this.open[g] ?? true },
            toggle(g){ this.open[g] = !this.isOpen(g); localStorage.setItem('nirwana-sb-groups', JSON.stringify(this.open)); }
         }"
         x-init="if (@js($activeGroup)) { open[@js($activeGroup)] = true }">
        @foreach ($urutanGrup as $g)
            @php $daftar = $grouped[$g] ?? collect(); @endphp
            @continue($daftar->isEmpty())
            <div class="px-3">
                @if ($g !== '')
                    <button type="button" class="nv-group nv-group-btn sb-label" @click="toggle('{{ $g }}')"
                            :aria-expanded="isOpen('{{ $g }}') ? 'true' : 'false'">
                        <span>{{ $g }}</span>
                        <svg class="nv-chevron" :class="{ 'nv-chevron-collapsed': !isOpen('{{ $g }}') }"
                             width="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                @endif
                <div class="nv-group-items space-y-0.5"
                     @if ($g !== '') x-show="isOpen('{{ $g }}')" x-cloak @endif>
                    @foreach ($daftar as $it)
                        @php $placeholder = $it['route'] === null; @endphp
                        <a href="{{ \App\Support\NavMenu::href($it) }}" title="{{ $it['label'] }}"
                           @class(['nv-item', 'nv-active' => $it['id'] === $active, 'nv-soon' => $placeholder])
                           @if ($placeholder) aria-disabled="true" @endif>
                            <span class="nv-ic"><x-icon :name="$it['icon']" /></span>
                            <span class="flex-1 sb-label">{{ $it['label'] }}</span>
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
        <div class="flex items-center gap-1">
            <a href="{{ route('profil') }}" title="Profil Saya" class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-white/5 cursor-pointer transition flex-1 min-w-0">
                <span class="avatar w-8 h-8 text-xs" style="background:var(--brand-200);color:var(--brand-800)">
                    {{ \Illuminate\Support\Str::of(auth()->user()?->name ?? 'U')->explode(' ')->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                </span>
                <div class="leading-tight flex-1 min-w-0 sb-label">
                    <div class="text-[13px] font-semibold text-white truncate">{{ auth()->user()?->name ?? 'Pengguna' }}</div>
                    <div class="text-[11px] text-brand-200/70 truncate">{{ auth()->user()?->getRoleNames()->implode(' · ') ?: 'Karyawan' }}</div>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="sb-label shrink-0">
                @csrf
                <button type="submit" title="Keluar" aria-label="Keluar"
                        class="w-9 h-9 grid place-items-center rounded-lg text-brand-200/70 hover:text-white hover:bg-white/5 transition">
                    <x-icon name="logout" :size="18" />
                </button>
            </form>
        </div>
    </div>
</aside>
