<div class="space-y-5">
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex items-center gap-1">
            <button class="btn btn-secondary btn-icon btn-sm" wire:click="bulanSebelumnya" title="Bulan sebelumnya">
                <svg width="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <span class="font-bold text-sm px-2 tnum min-w-[120px] text-center">{{ $namaBulan }}</span>
            <button class="btn btn-secondary btn-icon btn-sm" wire:click="bulanBerikutnya" title="Bulan berikutnya">
                <svg width="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
        <div class="flex-1"></div>
        @if(session('sukses'))<span class="text-xs text-success-600 font-semibold">{{ session('sukses') }}</span>@endif
    </div>

    <div class="flex flex-wrap items-center gap-3 text-xs">
        <span class="font-semibold text-neutral-400">Kode:</span>
        @forelse($daftarShift as $s)
            <span class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded grid place-items-center text-[11px] font-extrabold" style="background:{{ $s->warna }}1f;color:{{ $s->warna }}">{{ $s->kode }}</span>{{ $s->nama }}</span>
        @empty
            <span class="text-neutral-400">belum ada shift — buat dulu di tab Shift Unit</span>
        @endforelse
        <span class="text-neutral-400">· kosong / L = libur</span>
    </div>
    @error('jadwal')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror

    @forelse($blokJadwal as $b)
        <div class="space-y-2" wire:key="blok-{{ $b['pola']?->id ?? 'tanpa' }}">
            <div class="flex flex-wrap items-center gap-2">
                <div class="text-sm font-bold">{{ $b['nama'] }}</div>
                <span class="text-xs text-neutral-400">{{ $b['karyawan']->count() }} orang</span>
                @if($b['pola'])
                    <button class="btn btn-secondary btn-sm"
                            x-on:click="$store.konfirmasi.buka({ judul: 'Terapkan pola?', pesan: @js('Jadwal '.$namaBulan.' untuk anggota '.$b['nama'].' akan ditimpa (edit manual hilang).'), varian: 'primary', labelYa: 'Terapkan', onConfirm: () => $wire.terapkanPola({{ $b['pola']->id }}) })">
                        Terapkan {{ $b['nama'] }}
                    </button>
                @endif
            </div>

            <div class="grid-wrap rounded-xl border border-neutral-200">
                <table class="sched">
                    <thead>
                        <tr>
                            <th class="nm">Karyawan</th>
                            @for($d = 1; $d <= $jumlahHari; $d++)
                                @php($we = \Illuminate\Support\Carbon::create($tahun, $bulan, $d)->isWeekend())
                                <th @class(['we' => $we])>{{ $d }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($b['karyawan'] as $k)
                            <tr wire:key="jrow-{{ $tahun }}-{{ $bulan }}-{{ $k->id }}">
                                <td class="nm"><div class="font-semibold leading-tight">{{ $k->nama_lengkap }}</div><div class="text-[11px] text-neutral-400">{{ $k->jabatan?->nama }}</div></td>
                                @for($d = 1; $d <= $jumlahHari; $d++)
                                    @php($kodeSel = (array) ($petaJadwal[$k->id][$d] ?? []))
                                    @php($isiSel = implode(',', $kodeSel))
                                    @php($warna = $warnaKode[strtoupper((string) ($kodeSel[0] ?? ''))] ?? null)
                                    @php($we = \Illuminate\Support\Carbon::create($tahun, $bulan, $d)->isWeekend())
                                    <td @class(['relative' => true, 'we' => $we && ! $warna]) @style(['background:'.$warna.'26' => $warna])>
                                        <input class="cell-input" maxlength="11"
                                            wire:key="jad-{{ $tahun }}-{{ $bulan }}-{{ $k->id }}-{{ $d }}"
                                            value="{{ $isiSel }}"
                                            @style(['color:'.$warna => $warna])
                                            wire:change="setSel({{ $k->id }}, {{ $d }}, $event.target.value)">
                                        @if(count($kodeSel) > 1)
                                            <span class="absolute top-0.5 right-0.5 w-1.5 h-1.5 rounded-full bg-warning-500" title="Dinas ganda"></span>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="text-center text-sm text-neutral-400 py-6">Belum ada karyawan di unit ini.</div>
    @endforelse

    <p class="text-xs text-neutral-400 text-center">Ketik kode shift per orang per hari. Dinas ganda: pisahkan dengan koma, mis. <span class="font-mono font-semibold">M,S</span>. Shift malam lintas hari ditangani otomatis di absensi (model sesi). Tanpa jadwal = mode catat (tak dievaluasi telat).</p>
</div>
