@props(['title' => '', 'brand' => false, 'back' => null])
<header class="m-appbar {{ $brand ? 'brand' : '' }}">
    @if ($brand)
        <div class="flex items-center gap-2.5">
            <span class="grid place-items-center w-9 h-9 rounded-xl bg-white shadow-sm border border-neutral-100"><x-logo :size="22" /></span>
            <div class="leading-tight">
                <div class="font-extrabold text-[15px] tracking-tight text-white">Nirwana<span class="text-brand-300">HRIS</span></div>
                <div class="text-[10px] text-white/70 font-semibold uppercase tracking-wider">RSU Nirwana</div>
            </div>
        </div>
        <div class="flex-1"></div>
        <x-theme-toggle class="w-9 h-9 grid place-items-center rounded-full hover:bg-white/10 text-white" />
        <button type="button" class="w-9 h-9 grid place-items-center rounded-full hover:bg-white/10 text-white relative" aria-label="notifikasi">
            <x-icon name="bell" :size="20" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full" style="background:var(--danger-500)"></span>
        </button>
    @else
        @if ($back)
            <a href="{{ $back }}" class="w-9 h-9 grid place-items-center rounded-full hover:bg-neutral-100 -ml-1.5" aria-label="kembali">
                <x-icon name="back" :size="22" stroke-width="2" />
            </a>
        @endif
        <h1 class="font-bold text-[16px] tracking-tight">{{ $title }}</h1>
        <div class="flex-1"></div>
        <x-theme-toggle class="w-9 h-9 grid place-items-center rounded-full hover:bg-neutral-100" />
        {{ $slot }}
    @endif
</header>
