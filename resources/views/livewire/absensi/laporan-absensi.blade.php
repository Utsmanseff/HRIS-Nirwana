<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Laporan Absensi</h1>
            <p class="text-neutral-500 text-sm mt-1">Rekap kehadiran, keterlambatan, dan anomali.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['format' => 'xlsx'])) }}" class="btn btn-secondary">Ekspor Excel</a>
            <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['format' => 'pdf'])) }}" class="btn btn-secondary">Ekspor PDF</a>
            <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['mode' => 'per-unit', 'format' => 'xlsx'])) }}" class="btn btn-secondary" title="Semua unit, sheet terpisah per unit, urut nama">Excel per Unit</a>
            <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['mode' => 'per-unit', 'format' => 'pdf'])) }}" class="btn btn-secondary" title="Semua unit, halaman terpisah per unit, urut nama">PDF per Unit</a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="card card-pad"><div class="field-label">Hadir</div><div class="text-3xl font-extrabold tnum text-success-600">{{ $stat['hadir'] }}</div></div>
        <div class="card card-pad"><div class="field-label">Telat</div><div class="text-3xl font-extrabold tnum text-warning-600">{{ $stat['telat'] }}</div></div>
        <div class="card card-pad"><div class="field-label">Anomali</div><div class="text-3xl font-extrabold tnum text-danger-600">{{ $stat['anomali'] }}</div></div>
    </div>

    {{-- Filter --}}
    <div class="card card-pad flex flex-wrap items-center gap-2.5">
        <input type="date" wire:model.live="dari" class="input w-auto">
        <span class="text-neutral-400 text-sm">s/d</span>
        <input type="date" wire:model.live="sampai" class="input w-auto">
        <input wire:model.live.debounce.400ms="cari" class="input w-auto flex-1 min-w-[180px]" placeholder="Cari nama / NIP…">
        <select wire:model.live="unit" class="select w-auto">
            <option value="">Semua Unit</option>
            @foreach ($unitOpsi as $u)
                <option value="{{ $u->id }}">{{ $u->nama }}</option>
            @endforeach
        </select>
        <select wire:model.live="status" class="select w-auto">
            <option value="">Semua Status</option>
            <option value="normal">Normal</option>
            <option value="telat">Telat</option>
            <option value="pulang_cepat">Pulang cepat</option>
            <option value="anomali">Anomali</option>
        </select>
    </div>

    {{-- Tabel --}}
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Shift</th><th>Masuk</th><th>Pulang</th><th>Jam Kerja</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($baris as $a)
                    @php [$label, $kelas] = $a->labelStatus(); @endphp
                    <tr>
                        <td class="tnum">{{ $a->tanggal_kerja->format('d/m/Y') }}</td>
                        <td>
                            <div class="font-semibold">{{ $a->karyawan->nama_lengkap }}</div>
                            <div class="text-xs text-neutral-400">{{ $a->karyawan->jabatan?->nama }}</div>
                        </td>
                        <td>{{ $a->shift_nama ?? '—' }}</td>
                        <td class="tnum">{{ $a->jam_masuk?->format('H:i') ?? '—' }}</td>
                        <td class="tnum">{{ $a->jam_pulang?->format('H:i') ?? '—' }}</td>
                        <td class="tnum">{{ $a->totalMenit() ? intdiv($a->totalMenit(), 60).'j '.($a->totalMenit() % 60).'m' : '—' }}</td>
                        <td><span class="badge {{ $kelas }}">{{ $label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-neutral-400 py-8">Tak ada data pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
