<div class="space-y-4 rise">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight">Riwayat</h1>
        <p class="text-neutral-500 text-sm mt-1">Aktivitas Anda lintas modul, terbaru di atas.</p>
    </div>

    {{-- Filter chip per-jenis --}}
    @php
        $chips = [
            '' => 'Semua',
            'cuti' => 'Cuti',
            'tiket' => 'Tiket',
            'sanksi' => 'Sanksi',
            'absensi' => 'Absensi',
        ];
    @endphp
    <div class="flex flex-wrap gap-2">
        @foreach ($chips as $key => $label)
            <button type="button" wire:key="chip-{{ $key ?: 'semua' }}" wire:click="pilihJenis('{{ $key }}')"
                @class([
                    'px-3.5 py-1.5 rounded-full text-[13px] font-semibold border transition',
                    'bg-brand-600 text-white border-brand-600' => $jenisAktif === $key,
                    'bg-transparent text-neutral-500 border-neutral-200 hover:border-brand-400' => $jenisAktif !== $key,
                ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="divide-y divide-neutral-100">
            @forelse ($daftar as $e)
                @php $tag = $e['url'] ? 'a' : 'div'; @endphp
                <{{ $tag }}
                    wire:key="ev-{{ $e['jenis'] }}-{{ $e['waktu']->timestamp }}-{{ $loop->index }}"
                    @if ($e['url']) href="{{ $e['url'] }}" @endif
                    class="flex gap-3 px-4 py-3.5 {{ $e['url'] ? 'hover:bg-neutral-50 transition' : '' }}">
                    <span class="shrink-0 w-10 h-10 rounded-xl grid place-items-center"
                          style="background:var(--{{ $e['warna'] }}-50);color:var(--{{ $e['warna'] }}-600)">
                        <x-icon :name="$e['ikon']" :size="20" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-neutral-800 leading-snug">{{ $e['judul'] }}</div>
                        <div class="text-xs text-neutral-500 mt-0.5 truncate">{{ $e['detail'] }}</div>
                    </div>
                    <div class="shrink-0 text-[11px] text-neutral-400 text-right whitespace-nowrap">
                        {{ $e['waktu']->translatedFormat('j M') }}
                        <div class="text-neutral-300">{{ $e['waktu']->format('H:i') }}</div>
                    </div>
                </{{ $tag }}>
            @empty
                <div class="px-4 py-16 text-center">
                    <span class="mx-auto w-14 h-14 rounded-2xl bg-neutral-100 text-neutral-300 grid place-items-center mb-3">
                        <x-icon name="history" :size="26" />
                    </span>
                    <div class="text-sm font-semibold text-neutral-500">Belum ada aktivitas</div>
                    <p class="text-xs text-neutral-400 mt-1">Cuti, absensi, tiket & sanksi Anda akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
        {{ $daftar->links('livewire.sdm.partials.pager') }}
    </div>
</div>
