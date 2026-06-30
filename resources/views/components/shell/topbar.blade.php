@props(['title' => '', 'breadcrumb' => []])
<header class="topbar">
    <button type="button" class="lg:hidden btn btn-ghost btn-icon" onclick="document.body.classList.toggle('sb-open')" aria-label="menu">
        <x-icon name="menu" :size="20" stroke-width="2" />
    </button>

    <div class="min-w-0">
        @if (!empty($breadcrumb))
            <nav class="hidden sm:flex items-center gap-1.5 text-xs text-neutral-400 mb-0.5">
                @foreach ($breadcrumb as $i => $crumb)
                    @if ($i === array_key_last($breadcrumb))
                        <span class="text-neutral-700 font-semibold">{{ $crumb }}</span>
                    @else
                        <a href="#" class="text-neutral-400 hover:text-neutral-700">{{ $crumb }}</a>
                        <span class="text-neutral-300">/</span>
                    @endif
                @endforeach
            </nav>
        @endif
        <h1 class="text-[17px] font-bold tracking-tight leading-tight truncate">{{ $title }}</h1>
    </div>

    <div class="flex-1"></div>

    <div class="hidden md:flex items-center gap-2 px-3 h-9 w-64 rounded-md border border-neutral-200 bg-neutral-50 text-neutral-400 cursor-text">
        <x-icon name="search" :size="16" stroke-width="2" />
        <span class="text-sm flex-1">Cari karyawan, NIP…</span>
        <span class="kbd">⌘K</span>
    </div>

    <x-theme-toggle class="btn btn-ghost btn-icon" />

    <button type="button" class="btn btn-ghost btn-icon relative" aria-label="notifikasi">
        <x-icon name="bell" :size="20" />
        <span class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-danger-500 text-white text-[9px] font-bold grid place-items-center">3</span>
    </button>

    <span class="avatar w-9 h-9 text-xs md:hidden">{{ \Illuminate\Support\Str::of(auth()->user()?->name ?? 'U')->explode(' ')->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
</header>
