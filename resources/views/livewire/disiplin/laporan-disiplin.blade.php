<div class="mx-auto max-w-5xl">
    <h1 class="text-xl font-bold mb-4">Laporan Sanksi Disiplin</h1>

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
            <div><label class="field-label">Tingkat</label>
                <select wire:model.live="tingkat" class="select w-auto">
                    <option value="">Semua tingkat</option>
                    @foreach($tingkatOpsi as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="field-label">Status</label>
                <select wire:model.live="status" class="select w-auto">
                    <option value="">Semua status</option>
                    @foreach($statusOpsi as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
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
        <div class="card card-pad" data-strip="diterbitkan">
            <div class="field-label text-success-600">Diterbitkan</div>
            <div class="text-2xl font-bold tnum">{{ $status_hitung['diterbitkan'] }}</div>
        </div>
        <div class="card card-pad" data-strip="ditolak">
            <div class="field-label text-danger-600">Ditolak</div>
            <div class="text-2xl font-bold tnum">{{ $status_hitung['ditolak'] }}</div>
        </div>
        <div class="card card-pad" data-strip="dicabut">
            <div class="field-label text-neutral-500">Dicabut</div>
            <div class="text-2xl font-bold tnum">{{ $status_hitung['dicabut'] }}</div>
        </div>
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Karyawan</th><th>Unit</th><th>Tingkat</th><th>Tgl Kejadian</th><th>Pengusul</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($sanksi as $s)
                    <tr wire:key="sk-{{ $s->id }}">
                        <td>
                            <div class="font-semibold">{{ $s->karyawan->nama_lengkap }}</div>
                            <div class="text-xs text-neutral-400 font-mono">{{ $s->karyawan->nip }}</div>
                        </td>
                        <td>{{ $s->karyawan->orgUnit?->nama ?? '—' }}</td>
                        <td>{{ $s->tingkat->label() }}</td>
                        <td class="tnum">{{ $s->tanggal_kejadian->format('d M Y') }}</td>
                        <td>{{ $s->pengusul->nama_lengkap }}</td>
                        <td><span class="badge">{{ $s->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-sm text-neutral-500">Tak ada sanksi pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        @include('livewire.sdm.partials.pager', ['paginator' => $sanksi])
    </div>

    {{-- Blok ekspor diisi Task 3 --}}
    <div class="card card-pad mt-4">
        <h3 class="font-semibold mb-1">Ekspor Rekap Sanksi</h3>
        <p class="text-xs text-neutral-500 mb-3">Ikut filter di atas (periode/unit/tingkat/status).</p>
        @if(Route::has('disiplin.laporan.sanksi'))
            <a href="{{ route('disiplin.laporan.sanksi', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-secondary btn-sm">Excel</a>
            <a href="{{ route('disiplin.laporan.sanksi', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-secondary btn-sm">PDF</a>
        @else
            <span class="text-xs text-neutral-400">Ekspor segera.</span>
        @endif
    </div>
</div>
