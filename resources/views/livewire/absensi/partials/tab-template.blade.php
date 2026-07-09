@php($hari = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'])
<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="field-label">Mode</label>
            <select class="select w-auto" wire:model.live="tplMode">
                <option value="rotasi">Rotasi (siklus kontinu)</option>
                <option value="mingguan">Mingguan (per nama hari)</option>
            </select>
        </div>
        @if($tplMode === 'rotasi')
            <div><label class="field-label">Tanggal jangkar (posisi 0)</label><input type="date" class="input w-auto" wire:model="tplJangkar"></div>
            <div><label class="field-label">Panjang siklus (hari)</label><input type="number" min="1" max="60" class="input w-24 tnum" wire:model.live="tplPanjang"></div>
        @endif
        <button class="btn btn-primary btn-sm" wire:click="simpanTemplate">Simpan Template</button>
        @if(session('sukses'))<span class="text-xs text-success-600">{{ session('sukses') }}</span>@endif
    </div>
    @error('tplJangkar')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
    @error('polaGrid')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror

    <p class="text-xs text-neutral-400">
        Ketik kode shift tiap sel ({{ $daftarShift->pluck('kode')->implode(' / ') ?: '—' }}); kosong atau L = libur.
        @if($tplMode === 'rotasi')
            Siklus berulang dari tanggal jangkar (abaikan nama hari). Fase antar-orang diatur dengan menggeser urutannya.
        @else
            Kolom = nama hari (Sen–Min), jam tetap tiap minggu.
        @endif
        Tukar shift di jadwal bulanan tidak mengubah template ini.
    </p>

    <div class="grid-wrap">
        <table class="sched">
            <thead>
                <tr>
                    <th class="nm">Karyawan</th>
                    @if($tplMode === 'mingguan')
                        @foreach($hari as $h)<th>{{ $h }}</th>@endforeach
                    @else
                        @for($p = 0; $p < $tplPanjang; $p++)<th>{{ $p + 1 }}</th>@endfor
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($kelolaan as $k)
                    <tr>
                        <td class="nm"><div class="font-semibold leading-tight">{{ $k->nama_lengkap }}</div><div class="text-[11px] text-neutral-400">{{ $k->jabatan?->nama }}</div></td>
                        @php($kol = $tplMode === 'mingguan' ? 7 : $tplPanjang)
                        @for($p = 0; $p < $kol; $p++)
                            <td><input class="cell-input" wire:model="polaGrid.{{ $k->id }}.{{ $p }}" maxlength="5"></td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
