<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Laporan Absensi</h1>
            <p class="text-neutral-500 text-sm mt-1">Rekap kehadiran, keterlambatan, dan anomali.</p>
        </div>

        {{-- Ekspor: 2 dropdown per format (pola NotificationBell) --}}
        <div class="flex flex-wrap gap-2">
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="btn btn-secondary">
                    Excel
                    <svg width="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-2 w-44 card shadow-lg z-50 overflow-hidden p-1">
                    <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['format' => 'xlsx'])) }}" class="block px-3 py-2 rounded-md text-sm hover:bg-neutral-50">Laporan Ini</a>
                    <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['mode' => 'per-unit', 'format' => 'xlsx'])) }}" class="block px-3 py-2 rounded-md text-sm hover:bg-neutral-50">Per Unit <span class="text-xs text-neutral-400">(sheet/unit)</span></a>
                </div>
            </div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="btn btn-secondary">
                    PDF
                    <svg width="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-2 w-44 card shadow-lg z-50 overflow-hidden p-1">
                    <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['format' => 'pdf'])) }}" class="block px-3 py-2 rounded-md text-sm hover:bg-neutral-50">Laporan Ini</a>
                    <a href="{{ route('absensi.laporan.unduh', array_merge($query, ['mode' => 'per-unit', 'format' => 'pdf'])) }}" class="block px-3 py-2 rounded-md text-sm hover:bg-neutral-50">Per Unit <span class="text-xs text-neutral-400">(hal/unit)</span></a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat cards: 4, derived aman --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card card-pad rise">
            <div class="field-label">Hadir</div>
            <div class="text-3xl font-extrabold tnum text-success-600">{{ $stat['hadir'] }}</div>
        </div>
        <div class="card card-pad rise" style="animation-delay:.04s">
            <div class="field-label">Telat</div>
            <div class="text-3xl font-extrabold tnum text-warning-600">{{ $stat['telat'] }}</div>
        </div>
        <div class="card card-pad rise" style="animation-delay:.08s">
            <div class="field-label">Pulang Cepat</div>
            <div class="text-3xl font-extrabold tnum text-warning-600">{{ $stat['pulang_cepat'] }}</div>
        </div>
        <div class="card card-pad rise" style="animation-delay:.12s">
            <div class="field-label">Anomali</div>
            <div class="text-3xl font-extrabold tnum text-danger-600">{{ $stat['anomali'] }}</div>
            <div class="text-xs text-neutral-400 mt-1">lupa pulang dll</div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card card-pad flex flex-wrap items-center gap-2.5">
        <input type="date" wire:model.live="dari" class="input w-auto">
        <span class="text-neutral-400 text-sm">s/d</span>
        <input type="date" wire:model.live="sampai" class="input w-auto">
        <div class="relative flex-1 min-w-[180px]">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400">
                <svg width="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4" stroke-linecap="round"/></svg>
            </span>
            <input wire:model.live.debounce.400ms="cari" class="input w-full" style="padding-left:2.35rem" placeholder="Cari nama / NIP…">
        </div>
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
                        <td>
                            @if ($a->shift_nama)
                                <span class="badge badge-neutral">
                                    @if ($a->shift?->warna)
                                        <span class="dot" style="background:{{ $a->shift->warna }}"></span>
                                    @endif
                                    {{ $a->shift_nama }}
                                </span>
                            @else
                                <span class="text-neutral-400">—</span>
                            @endif
                        </td>
                        <td class="tnum {{ $a->telat_menit ? 'text-warning-700' : '' }}">{{ $a->jam_masuk?->format('H:i') ?? '—' }}</td>
                        <td class="tnum">{{ $a->jam_pulang?->format('H:i') ?? '—' }}</td>
                        <td class="tnum">{{ $a->jamKerjaLabel() }}</td>
                        <td><span class="badge {{ $kelas }}"><span class="dot"></span>{{ $label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-neutral-400 py-8">Tak ada data pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3 text-center text-xs text-neutral-400 border-t border-neutral-100">
            Evaluasi by jadwal: punya shift → deteksi telat/pulang cepat; tanpa shift → catat saja + total jam. Sesi nyangkut (anomali) ditandai, tidak dikoreksi otomatis.
        </div>
    </div>
</div>
