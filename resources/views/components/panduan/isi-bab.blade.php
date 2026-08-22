@props(['bagian' => []])
@if (count($bagian) > 1)
    <nav class="card card-pad mb-5" aria-label="Daftar bagian bab ini">
        <div class="text-[12px] font-bold uppercase tracking-wide mb-2" style="color:var(--text-muted)">Isi Bab Ini</div>
        <ol class="list-decimal ms-5 space-y-1 text-[13.5px]">
            @foreach ($bagian as $b)
                <li><a href="#{{ $b['id'] }}" class="hover:underline" style="color:var(--brand-600)">{{ $b['judul'] }}</a></li>
            @endforeach
        </ol>
    </nav>
@endif
