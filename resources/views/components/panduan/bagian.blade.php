@props(['id', 'judul'])
<section class="mt-8">
    <div class="flex items-baseline gap-2">
        <h2 id="{{ $id }}" class="text-[18px] font-bold tracking-tight flex-1">{{ $judul }}</h2>
        <a href="#atas" class="text-[11px] shrink-0 hover:underline" style="color:var(--text-muted)">↑ Bagian atas</a>
    </div>
    <div class="mt-2">
        {{ $slot }}
    </div>
</section>
