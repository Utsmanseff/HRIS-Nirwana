@php($hari = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'])
@php($ada = $urutanGrid)
@php($byId = $kelolaan->keyBy('id'))
<div class="space-y-5">
    <div class="flex flex-wrap items-center gap-2">
        @foreach($daftarPola as $p)
            <button type="button" wire:key="pola-{{ $p->id }}" wire:click="gantiPola({{ $p->id }})"
                @class(['px-3 py-1.5 text-sm font-semibold rounded-lg border transition',
                        'border-brand-200 text-brand-700' => $polaId === $p->id,
                        'border-neutral-200 text-neutral-500' => $polaId !== $p->id])
                @style(['background:var(--brand-50)' => $polaId === $p->id])>
                {{ $p->nama }}
            </button>
        @endforeach
        @unless($formPola)
            <button type="button" wire:click="bukaFormPola" class="btn btn-secondary btn-sm">+ Pola Baru</button>
        @else
            <div class="flex items-center gap-2">
                <input class="input w-auto" wire:model="pNama" placeholder="Nama pola, mis. Pola CS IGD" maxlength="60">
                @if($modeFormPola === 'ubah')
                    <button type="button" wire:click="ubahNamaPola" class="btn btn-primary btn-sm">Simpan Nama</button>
                @else
                    <button type="button" wire:click="buatPola" class="btn btn-primary btn-sm">Buat Pola</button>
                @endif
                <button type="button" wire:click="batalFormPola" class="btn btn-secondary btn-sm">Batal</button>
            </div>
        @endunless
    </div>
    @error('pNama')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror

    @if($daftarPola->isEmpty())
        <p class="text-sm text-neutral-400">Unit ini belum punya pola. Buat pola dulu, mis. "Pola CS IGD".</p>
    @endif

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
        @endif
        <button class="btn btn-primary btn-sm" wire:click="simpanTemplate">
            <svg width="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Simpan Pola
        </button>
        @if($polaAktif)
            <button type="button" class="btn btn-secondary btn-sm" wire:click="bukaFormUbahNama">Ubah Nama</button>
            <button type="button" class="btn btn-secondary btn-sm text-danger-600"
                    x-on:click="$store.konfirmasi.buka({ judul: 'Hapus pola?', pesan: @js('Pola \''.$polaAktif->nama.'\' dihapus. Jadwal yang sudah terbentuk tetap ada.'), varian: 'danger', labelYa: 'Hapus', onConfirm: () => $wire.hapusPola() })">Hapus Pola</button>
        @endif
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

    <div class="grid-wrap rounded-xl border border-neutral-200">
        <table class="sched">
            @if($tplMode === 'mingguan')
                <thead><tr><th class="nm">Karyawan</th>@foreach($hari as $h)<th>{{ $h }}</th>@endforeach<th></th></tr></thead>
            @else
                <thead><tr><th class="nm">Karyawan</th><th colspan="99"></th></tr></thead>
            @endif
            <tbody>
                @forelse($ada as $kid)
                    @php($k = $byId[$kid] ?? null)
                    @continue(! $k)
                    @php($kol = $tplMode === 'mingguan' ? 7 : (int)($panjangBaris[$kid] ?? 7))
                    <tr wire:key="trow-{{ $tplMode }}-{{ $kid }}">
                        <td class="nm"><div class="font-semibold leading-tight">{{ $k->nama_lengkap }}</div><div class="text-[11px] text-neutral-400">{{ $k->jabatan?->nama }}</div></td>
                        @for($p = 0; $p < $kol; $p++)
                            @php($kode = strtoupper(trim((string)($polaGrid[$kid][$p] ?? ''))))
                            @php($warna = $warnaKode[$kode] ?? null)
                            <td @style(['background:'.$warna.'26' => $warna])>
                                <input class="cell-input" wire:key="tpl-{{ $tplMode }}-{{ $kid }}-{{ $p }}" wire:model="polaGrid.{{ $kid }}.{{ $p }}" maxlength="5" @style(['color:'.$warna => $warna]) placeholder="·">
                            </td>
                        @endfor
                        <td class="whitespace-nowrap px-2">
                            <div class="flex items-center gap-1 justify-end">
                                @if($tplMode === 'rotasi')
                                    <button type="button" wire:click="kurangKolom({{ $kid }})" class="w-6 h-6 rounded border border-neutral-200 text-neutral-500 leading-none" title="Kurangi panjang siklus">−</button>
                                    <button type="button" wire:click="tambahKolom({{ $kid }})" class="w-6 h-6 rounded border border-neutral-200 text-neutral-500 leading-none" title="Tambah panjang siklus">+</button>
                                @endif
                                @if($tukarDari === null)
                                    <button type="button" wire:click="pilihTukar({{ $kid }})" class="text-xs text-neutral-500 hover:underline" title="Tukar baris dengan orang lain">tukar</button>
                                @elseif($tukarDari === $kid)
                                    <button type="button" wire:click="pilihTukar({{ $kid }})" class="text-xs font-bold text-brand-600 hover:underline">batal tukar</button>
                                @else
                                    <button type="button" wire:click="tukarBaris({{ $kid }})" class="text-xs font-bold text-brand-600 hover:underline">tukar ke sini</button>
                                @endif
                                <button type="button" wire:click="hapusBaris({{ $kid }})" class="ml-1 text-xs text-danger-600 hover:underline" title="Keluarkan dari pola">hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="nm text-neutral-400" colspan="99">Belum ada karyawan di template. Tambah lewat pilihan di bawah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="space-y-2">
        <label class="field-label">Tambah karyawan ke pola</label>

        @if($tukarLuarId)
            @php($namaLuar = $kelolaan->firstWhere('id', $tukarLuarId)?->nama_lengkap ?? 'karyawan')
            <div class="border border-brand-200 bg-brand-50/40 rounded-lg p-3 max-w-xl space-y-2">
                <p class="text-sm">Tukar <b>{{ $namaLuar }}</b> (dari {{ $polaLainPeta[$tukarLuarId] ?? 'pola lain' }}) dengan anggota pola ini — keduanya bertukar pola, jadwal tiap pola tetap:</p>
                @forelse($anggotaPolaAktif as $m)
                    <button type="button" wire:key="lawan-{{ $m->id }}" wire:click="tukarAntarPola({{ $m->id }})"
                            class="w-full text-left px-3 py-2 rounded hover:bg-brand-100/60 border border-neutral-200">
                        <span class="text-sm font-semibold">{{ $m->nama_lengkap }}</span>
                        <span class="text-xs text-neutral-400">· {{ $m->nip }}</span>
                    </button>
                @empty
                    <p class="text-xs text-neutral-400">Pola ini belum punya anggota tersimpan untuk ditukar. Simpan anggota dulu.</p>
                @endforelse
                <button type="button" wire:click="batalTukarLuar" class="btn btn-secondary btn-sm">Batal</button>
            </div>
        @else
            <input class="input w-full sm:w-80" wire:model.live.debounce.300ms="cariAnggota"
                   placeholder="Cari nama atau NIP…">
            @if($hasilCariAnggota->isNotEmpty())
                <div class="border border-neutral-200 rounded-lg divide-y divide-neutral-100 max-w-xl">
                    @foreach($hasilCariAnggota as $c)
                        <div wire:key="cari-{{ $c->id }}" class="flex items-center justify-between gap-2 px-3 py-2 hover:bg-neutral-50">
                            <div>
                                <span class="text-sm font-semibold">{{ $c->nama_lengkap }}</span>
                                <span class="text-xs text-neutral-400">· {{ $c->nip }}</span>
                                @if(isset($polaLainPeta[$c->id]))
                                    <span class="ml-1 text-[11px] font-bold text-warning-600">sudah di {{ $polaLainPeta[$c->id] }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if(isset($polaLainPeta[$c->id]))
                                    <button type="button" wire:click="mulaiTukarLuar({{ $c->id }})" class="text-xs font-semibold text-brand-600 hover:underline">Tukar dengan…</button>
                                    <button type="button" wire:click="tambahKaryawan({{ $c->id }})" class="text-xs text-neutral-500 hover:underline" title="Pindahkan (jadwal lama di pola asal dibuang)">Pindahkan</button>
                                @else
                                    <button type="button" wire:click="tambahKaryawan({{ $c->id }})" class="btn btn-secondary btn-sm">Tambah</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif(trim($cariAnggota) !== '')
                <p class="text-xs text-neutral-400">Tak ada karyawan cocok.</p>
            @endif
        @endif
    </div>
</div>
