<div>
    {{-- Header: nav bulan + filter unit (HRD) --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-2">
            <button wire:click="bulanSebelumnya" class="btn btn-ghost btn-sm" aria-label="Bulan sebelumnya">‹</button>
            <div class="font-bold text-sm min-w-32 text-center">{{ $bulanLabel }}</div>
            <button wire:click="bulanBerikutnya" class="btn btn-ghost btn-sm" aria-label="Bulan berikutnya">›</button>
        </div>
        @if($isHrd)
            <select wire:model.live="unitId" class="select w-auto">
                <option value="">Semua unit</option>
                @foreach($daftarUnit as $u)<option value="{{ $u->id }}">{{ $u->nama }}</option>@endforeach
            </select>
        @endif
    </div>

    {{-- Grid bulan --}}
    <div class="grid grid-cols-7 gap-px rounded-lg overflow-hidden border border-neutral-200 bg-neutral-200">
        @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $h)
            <div class="bg-neutral-50 text-center text-[11px] font-semibold text-neutral-500 py-1.5">{{ $h }}</div>
        @endforeach

        @foreach($minggu as $baris)
            @foreach($baris as $s)
                @php $isi = $hari[$s['ymd']] ?? collect(); @endphp
                <div @class([
                        'bg-white min-h-20 p-1 align-top',
                        'opacity-40' => $s['luar'],
                        'cursor-pointer hover:bg-brand-50' => $isi->isNotEmpty(),
                    ])
                    @if($isi->isNotEmpty()) wire:click="pilihHari('{{ $s['ymd'] }}')" @endif>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-neutral-400">{{ $s['tanggal']->day }}</span>
                        {{-- Mobile: count-dot --}}
                        @if($isi->isNotEmpty())
                            <span class="sm:hidden inline-grid place-items-center w-4 h-4 rounded-full bg-brand-500 text-white text-[9px]">{{ $isi->count() }}</span>
                        @endif
                    </div>
                    {{-- Desktop: chip nama, cap 3 --}}
                    <div class="hidden sm:block mt-0.5 space-y-0.5">
                        @foreach($isi->take(3) as $c)
                            <div data-status="{{ $c['status'] }}"
                                @class([
                                    'text-[10px] leading-tight rounded px-1 truncate',
                                    'bg-brand-100 text-brand-800' => $c['status'] === 'disetujui',
                                    'border border-warning-400 text-warning-700' => $c['status'] !== 'disetujui',
                                ])>{{ $c['nama'] }}</div>
                        @endforeach
                        @if($isi->count() > 3)
                            <div class="text-[10px] text-neutral-400">+{{ $isi->count() - 3 }} lagi</div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    {{-- Panel detail hari terpilih --}}
    @php $detail = $hariAktif ? ($hari[$hariAktif] ?? collect()) : collect(); @endphp
    @if($hariAktif && $detail->isNotEmpty())
        <div data-panel="hari" class="card card-pad mt-4">
            <div class="flex items-center justify-between mb-2">
                <div class="font-semibold text-sm">{{ \Illuminate\Support\Carbon::parse($hariAktif)->locale('id')->translatedFormat('l, j F Y') }}</div>
                <button wire:click="pilihHari('{{ $hariAktif }}')" class="btn btn-ghost btn-sm">Tutup</button>
            </div>
            <div class="space-y-1.5">
                @foreach($detail as $c)
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <div>
                            <span class="font-semibold">{{ $c['nama'] }}</span>
                            <span class="text-xs text-neutral-400 font-mono ml-1">{{ $c['nip'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-neutral-500">{{ $c['jenis'] }}</span>
                            <span @class([
                                'badge',
                                'badge-success' => $c['status'] === 'disetujui',
                                'badge-warning' => $c['status'] !== 'disetujui',
                            ]) data-status="{{ $c['status'] }}">{{ ucfirst($c['status']) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
