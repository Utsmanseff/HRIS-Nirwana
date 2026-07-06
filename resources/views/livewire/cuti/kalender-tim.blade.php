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
                    ])>
                    <div class="text-[11px] font-semibold text-neutral-400">{{ $s['tanggal']->day }}</div>
                    @foreach($isi as $c)
                        <div class="text-[11px] truncate">{{ $c['nama'] }}</div>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>
</div>
