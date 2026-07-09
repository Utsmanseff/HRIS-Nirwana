@php($hari = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'])
<div class="space-y-5">
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <label class="field-label">Mode template</label>
            <div class="inline-flex rounded-lg border border-neutral-200 p-0.5 bg-neutral-50">
                <button type="button" wire:click="$set('tplMode','rotasi')" @class(['px-3 py-1.5 text-sm font-semibold rounded-md transition', 'shadow-sm text-brand-600' => $tplMode==='rotasi', 'text-neutral-400' => $tplMode!=='rotasi']) @style(['background:var(--bg-surface)' => $tplMode==='rotasi'])>Rotasi</button>
                <button type="button" wire:click="$set('tplMode','mingguan')" @class(['px-3 py-1.5 text-sm font-semibold rounded-md transition', 'shadow-sm text-brand-600' => $tplMode==='mingguan', 'text-neutral-400' => $tplMode!=='mingguan']) @style(['background:var(--bg-surface)' => $tplMode==='mingguan'])>Mingguan</button>
            </div>
        </div>
        @if($tplMode === 'rotasi')
            <div><label class="field-label">Tanggal jangkar</label><input type="date" class="input w-auto" wire:model="tplJangkar"></div>
            <div><label class="field-label">Panjang siklus</label><input type="number" min="1" max="60" class="input w-20 tnum" wire:model.live="tplPanjang"></div>
        @endif
        <button class="btn btn-primary btn-sm" wire:click="simpanTemplate">
            <svg width="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Simpan Template
        </button>
        @if(session('sukses'))<span class="text-xs text-success-600 font-semibold self-center">{{ session('sukses') }}</span>@endif
    </div>
    @error('tplJangkar')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
    @error('polaGrid')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror

    <div class="flex flex-wrap items-center gap-3 text-xs">
        <span class="font-semibold text-neutral-400">Kode:</span>
        @forelse($daftarShift as $s)
            <span class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded grid place-items-center text-[11px] font-extrabold" style="background:{{ $s->warna }}1f;color:{{ $s->warna }}">{{ $s->kode }}</span>{{ $s->nama }}</span>
        @empty
            <span class="text-neutral-400">belum ada shift — buat dulu di tab Shift Unit</span>
        @endforelse
        <span class="text-neutral-400">· kosong / L = libur</span>
    </div>

    <p class="text-xs text-neutral-400 leading-relaxed">
        @if($tplMode === 'rotasi')
            <b>Rotasi:</b> siklus berulang terus dari tanggal jangkar (abaikan nama hari). Fase antar-orang diatur dengan menggeser urutannya. Cocok shift RS 24/7.
        @else
            <b>Mingguan:</b> kolom = nama hari (Sen–Min), jam tetap tiap minggu. Cocok office.
        @endif
        Tukar shift di jadwal bulanan tidak mengubah template ini.
    </p>

    @if($kelolaan->isEmpty())
        <div class="text-center text-sm text-neutral-400 py-6">Belum ada karyawan di unit ini.</div>
    @else
        <div class="grid-wrap rounded-xl border border-neutral-200">
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
                    @php($kol = $tplMode === 'mingguan' ? 7 : $tplPanjang)
                    @foreach($kelolaan as $k)
                        <tr wire:key="trow-{{ $tplMode }}-{{ $k->id }}">
                            <td class="nm"><div class="font-semibold leading-tight">{{ $k->nama_lengkap }}</div><div class="text-[11px] text-neutral-400">{{ $k->jabatan?->nama }}</div></td>
                            @for($p = 0; $p < $kol; $p++)
                                @php($kode = strtoupper(trim((string)($polaGrid[$k->id][$p] ?? ''))))
                                @php($warna = $warnaKode[$kode] ?? null)
                                <td @style(['background:'.$warna.'26' => $warna])>
                                    <input class="cell-input" wire:key="tpl-{{ $tplMode }}-{{ $k->id }}-{{ $p }}" wire:model="polaGrid.{{ $k->id }}.{{ $p }}" maxlength="5" @style(['color:'.$warna => $warna]) placeholder="·">
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
