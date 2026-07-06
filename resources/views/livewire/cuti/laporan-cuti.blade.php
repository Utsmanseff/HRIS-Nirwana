<div class="mx-auto max-w-5xl">
    <h1 class="text-xl font-bold mb-4">Laporan Cuti</h1>

    {{-- Filter --}}
    <div class="card card-pad mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div><label class="field-label">Dari</label><input type="date" wire:model.live="dari" class="input w-auto"></div>
            <div><label class="field-label">Sampai</label><input type="date" wire:model.live="sampai" class="input w-auto"></div>
            <div><label class="field-label">Unit</label>
                <select wire:model.live="unitId" class="select w-auto">
                    <option value="">Semua unit</option>
                    @foreach($daftarUnit as $u)<option value="{{ $u->id }}">{{ $u->nama }}</option>@endforeach
                </select>
            </div>
            <div><label class="field-label">Jenis</label>
                <select wire:model.live="jenisId" class="select w-auto">
                    <option value="">Semua jenis</option>
                    @foreach($daftarJenis as $j)<option value="{{ $j->id }}">{{ $j->nama }}</option>@endforeach
                </select>
            </div>
            <div><label class="field-label">Status</label>
                <select wire:model.live="status" class="select w-auto">
                    <option value="">Semua status</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="diproses">Diproses</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Strip status --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card card-pad" data-strip="pending">
            <div class="field-label text-warning-700">Pending</div>
            <div class="text-2xl font-bold tnum">{{ $status_hitung['diajukan'] + $status_hitung['diproses'] }}</div>
        </div>
        <div class="card card-pad" data-strip="disetujui">
            <div class="field-label text-success-600">Disetujui</div>
            <div class="text-2xl font-bold tnum">{{ $status_hitung['disetujui'] }}</div>
        </div>
        <div class="card card-pad" data-strip="ditolak">
            <div class="field-label text-danger-600">Ditolak</div>
            <div class="text-2xl font-bold tnum">{{ $status_hitung['ditolak'] }}</div>
        </div>
        <div class="card card-pad" data-strip="dibatalkan">
            <div class="field-label text-neutral-500">Dibatalkan</div>
            <div class="text-2xl font-bold tnum">{{ $status_hitung['dibatalkan'] }}</div>
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead><tr><th>Pemohon</th><th>Unit</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Hari</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($pengajuan as $p)
                    <tr wire:key="pj-{{ $p->id }}">
                        <td>
                            <div class="font-semibold">{{ $p->karyawan->nama_lengkap }}</div>
                            <div class="text-xs text-neutral-400 font-mono">{{ $p->karyawan->nip }}</div>
                        </td>
                        <td>{{ $p->karyawan->orgUnit?->nama ?? '—' }}</td>
                        <td>{{ $p->jenisCuti->nama }}</td>
                        <td class="tnum">{{ $p->tanggal_mulai->format('d M Y') }}</td>
                        <td class="tnum">{{ $p->tanggal_selesai->format('d M Y') }}</td>
                        <td class="tnum">{{ $p->jumlah_hari }}</td>
                        <td><span class="badge">{{ ucfirst($p->status->value) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-sm text-neutral-500">Tak ada pengajuan pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        @include('livewire.sdm.partials.pager', ['paginator' => $pengajuan])
    </div>

    {{-- Blok ekspor diisi Task 4/5 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
        <div class="card card-pad">
            <h3 class="font-semibold mb-1">Rekap Pengajuan</h3>
            <p class="text-xs text-neutral-500 mb-3">Ikut filter di atas (periode/unit/jenis/status).</p>
            @if(Route::has('cuti.laporan.pengajuan'))
                <a href="{{ route('cuti.laporan.pengajuan', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-secondary btn-sm">Excel</a>
                <a href="{{ route('cuti.laporan.pengajuan', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-secondary btn-sm">PDF</a>
            @else
                <span class="text-xs text-neutral-400">Ekspor segera.</span>
            @endif
        </div>
        <div class="card card-pad">
            <h3 class="font-semibold mb-1">Saldo Karyawan</h3>
            <p class="text-xs text-neutral-500 mb-3">Jatah / terpakai / sisa periode berjalan (filter unit saja).</p>
            @if(Route::has('cuti.laporan.saldo'))
                <a href="{{ route('cuti.laporan.saldo', array_filter(['unit_id' => $unitId, 'format' => 'xlsx'])) }}" class="btn btn-secondary btn-sm">Excel</a>
                <a href="{{ route('cuti.laporan.saldo', array_filter(['unit_id' => $unitId, 'format' => 'pdf'])) }}" class="btn btn-secondary btn-sm">PDF</a>
            @else
                <span class="text-xs text-neutral-400">Ekspor segera.</span>
            @endif
        </div>
    </div>
</div>
