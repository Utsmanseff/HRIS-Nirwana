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
        <button class="btn btn-secondary btn-sm" wire:click="terapkanPola"
                wire:confirm="Terapkan template pola ke {{ $namaBulan }}? Jadwal bulan ini untuk karyawan bersiklus akan ditimpa (edit manual hilang).">
            <svg width="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Terapkan Pola
        </button>
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

    @if($kelolaan->isEmpty())
        <div class="text-center text-sm text-neutral-400 py-6">Belum ada karyawan di unit ini.</div>
    @else
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
                    @foreach($kelolaan as $k)
                        <tr wire:key="jrow-{{ $tahun }}-{{ $bulan }}-{{ $k->id }}">
                            <td class="nm"><div class="font-semibold leading-tight">{{ $k->nama_lengkap }}</div><div class="text-[11px] text-neutral-400">{{ $k->jabatan?->nama }}</div></td>
                            @for($d = 1; $d <= $jumlahHari; $d++)
                                @php($kode = strtoupper((string)($petaJadwal[$k->id][$d] ?? '')))
                                @php($warna = $warnaKode[$kode] ?? null)
                                @php($we = \Illuminate\Support\Carbon::create($tahun, $bulan, $d)->isWeekend())
                                <td @class(['we' => $we && ! $warna]) @style(['background:'.$warna.'26' => $warna])>
                                    <input class="cell-input" maxlength="5"
                                        wire:key="jad-{{ $tahun }}-{{ $bulan }}-{{ $k->id }}-{{ $d }}"
                                        value="{{ $petaJadwal[$k->id][$d] ?? '' }}"
                                        @style(['color:'.$warna => $warna])
                                        wire:change="setSel({{ $k->id }}, {{ $d }}, $event.target.value)">
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-neutral-400 text-center">Ketik kode untuk ubah shift per orang per hari. Shift malam lintas hari ditangani otomatis di absensi (model sesi). Tanpa jadwal = mode catat (tak dievaluasi telat).</p>
    @endif
</div>
