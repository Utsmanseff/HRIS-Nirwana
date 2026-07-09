<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="flex items-center gap-1">
            <button class="btn btn-secondary btn-icon btn-sm" wire:click="bulanSebelumnya">‹</button>
            <span class="font-bold text-sm px-2 tnum">{{ $namaBulan }}</span>
            <button class="btn btn-secondary btn-icon btn-sm" wire:click="bulanBerikutnya">›</button>
        </div>
        <div class="flex-1"></div>
        <button class="btn btn-secondary btn-sm" wire:click="terapkanPola"
                wire:confirm="Terapkan template pola ke {{ $namaBulan }}? Jadwal bulan ini untuk karyawan bersiklus akan ditimpa.">Terapkan Pola</button>
        @if(session('sukses'))<span class="text-xs text-success-600">{{ session('sukses') }}</span>@endif
    </div>

    <div class="px-1 py-2 flex flex-wrap items-center gap-3 text-xs">
        <span class="font-semibold text-neutral-400">Kode:</span>
        @foreach($daftarShift as $s)
            <span class="inline-flex items-center gap-1.5"><span class="w-5 h-5 rounded grid place-items-center text-[11px] font-bold" style="background:{{ $s->warna }}22;color:{{ $s->warna }}">{{ $s->kode }}</span>{{ $s->nama }}</span>
        @endforeach
        <span class="text-neutral-400">· kosong / L = libur</span>
    </div>
    @error('jadwal')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror

    <div class="grid-wrap">
        <table class="sched">
            <thead>
                <tr>
                    <th class="nm">Karyawan</th>
                    @for($d = 1; $d <= $jumlahHari; $d++)<th>{{ $d }}</th>@endfor
                </tr>
            </thead>
            <tbody>
                @foreach($kelolaan as $k)
                    <tr>
                        <td class="nm"><div class="font-semibold leading-tight">{{ $k->nama_lengkap }}</div><div class="text-[11px] text-neutral-400">{{ $k->jabatan?->nama }}</div></td>
                        @for($d = 1; $d <= $jumlahHari; $d++)
                            <td><input class="cell-input" maxlength="5"
                                value="{{ $petaJadwal[$k->id][$d] ?? '' }}"
                                wire:change="setSel({{ $k->id }}, {{ $d }}, $event.target.value)"></td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-neutral-400 text-center">Ketik kode untuk ubah shift per orang per hari. Shift malam lintas hari ditangani otomatis di absensi (model sesi). Tanpa jadwal = mode catat (tak dievaluasi telat).</p>
</div>
