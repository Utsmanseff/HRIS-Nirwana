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

    {{-- Tabel + ekspor diisi Task 3/4/5 --}}
    <div class="card">
        <div class="card-pad text-sm text-neutral-500">Tabel & ekspor segera diisi.</div>
    </div>
</div>
