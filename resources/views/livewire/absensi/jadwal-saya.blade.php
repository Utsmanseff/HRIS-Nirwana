<div class="space-y-4 rise">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight">Jadwal Saya</h1>
        <p class="text-neutral-500 text-sm mt-1">Shift kerja Anda per bulan.</p>
    </div>

    {{-- Navigasi bulan --}}
    <div class="flex items-center justify-between gap-3">
        <button type="button" wire:click="geser(-1)" class="btn btn-secondary btn-icon" aria-label="bulan sebelumnya">
            <x-icon name="back" :size="18" stroke-width="2" />
        </button>
        <div class="font-bold text-[15px]">{{ $labelBulan }}</div>
        <button type="button" wire:click="geser(1)" class="btn btn-secondary btn-icon" aria-label="bulan berikutnya">
            <x-icon name="back" :size="18" stroke-width="2" class="rotate-180" />
        </button>
    </div>

    <div class="card overflow-hidden">
        <div class="divide-y divide-neutral-100">
            @forelse ($hari as $tanggal => $baris)
                @php
                    $tgl = \Illuminate\Support\Carbon::parse($tanggal);
                    $ini = $tgl->isToday();
                @endphp
                <div class="flex items-start gap-3 px-4 py-3 {{ $ini ? 'bg-brand-50/50' : '' }}" wire:key="hari-{{ $tanggal }}">
                    <div class="shrink-0 w-11 text-center pt-0.5">
                        <div class="text-[10px] font-semibold uppercase {{ $ini ? 'text-brand-600' : 'text-neutral-400' }}">{{ $tgl->locale('id')->translatedFormat('D') }}</div>
                        <div class="text-lg font-extrabold tnum leading-none {{ $ini ? 'text-brand-700' : 'text-neutral-700' }}">{{ $tgl->format('j') }}</div>
                    </div>
                    <div class="min-w-0 flex-1 space-y-2">
                        @foreach ($baris as $j)
                            @php
                                $s = $j->shift;
                                $warna = $s?->warna ?? '#94a3b8';
                            @endphp
                            <div wire:key="jad-{{ $j->id }}">
                                @if ($s)
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $warna }}"></span>
                                        <span class="text-sm font-semibold text-neutral-800">{{ $s->nama }}</span>
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:{{ $warna }}1a;color:{{ $warna }}">{{ $s->kode }}</span>
                                    </div>
                                    <div class="text-xs text-neutral-500 mt-0.5 tnum">{{ \Illuminate\Support\Str::substr($s->jam_mulai, 0, 5) }}–{{ \Illuminate\Support\Str::substr($s->jam_selesai, 0, 5) }}</div>
                                @else
                                    <span class="text-sm text-neutral-400">Libur</span>
                                @endif
                            </div>
                        @endforeach
                        @if (count($baris) > 1)
                            <div class="text-[10px] font-bold uppercase tracking-wide text-warning-600">Dinas ganda</div>
                        @endif
                    </div>
                    @if ($ini)
                        <span class="shrink-0 text-[10px] font-bold text-brand-600 uppercase tracking-wide pt-1">Hari ini</span>
                    @endif
                </div>
            @empty
                <div class="px-4 py-16 text-center">
                    <span class="mx-auto w-14 h-14 rounded-2xl bg-neutral-100 text-neutral-300 grid place-items-center mb-3">
                        <x-icon name="calendar" :size="26" />
                    </span>
                    <div class="text-sm font-semibold text-neutral-500">Belum ada jadwal</div>
                    <p class="text-xs text-neutral-400 mt-1">Tidak ada shift terjadwal bulan ini. Unit Anda mungkin tidak memakai sistem shift.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
