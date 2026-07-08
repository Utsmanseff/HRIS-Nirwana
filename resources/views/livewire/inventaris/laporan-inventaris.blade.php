<div class="space-y-4 rise">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div><h1 class="text-lg font-extrabold tracking-tight">Laporan Inventaris</h1>
            <p class="text-sm text-neutral-500">Daftar aset tim {{ implode(', ', array_map(fn ($t) => $t->label(), auth()->user()->timTeknis())) }}.</p></div>
        <a href="{{ route('inventaris') }}" class="btn btn-secondary btn-sm">← Inventaris</a>
    </div>

    {{-- Filter --}}
    <div class="card p-4 flex flex-wrap items-center gap-2.5">
        <select wire:model.live="kategoriId" class="select" style="width:auto">
            <option value="">Semua Kategori</option>
            @foreach ($kategoriList as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
        </select>
        <select wire:model.live="unitId" class="select" style="width:auto">
            <option value="">Semua Unit</option>
            @foreach ($unitList as $u)
                <option value="{{ $u->id }}">{{ $u->nama }}</option>
            @endforeach
        </select>
        <select wire:model.live="status" class="select" style="width:auto">
            <option value="">Semua Status</option>
            @foreach ($statusOpsi as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Strip status --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach ($statusOpsi as $s)
            <div class="card card-pad">
                <div class="field-label">{{ $s->label() }}</div>
                <div class="text-2xl font-bold tnum">{{ $strip[$s->value] ?? 0 }}</div>
            </div>
        @endforeach
    </div>

    {{-- Ekspor --}}
    <div class="grid gap-3 sm:grid-cols-2">
        @if (Route::has('inventaris.laporan.aset'))
            <div class="card card-pad flex items-center justify-between gap-2">
                <div><div class="font-semibold text-sm">Daftar Aset</div><div class="text-xs text-neutral-500">Ekspor sesuai filter</div></div>
                <div class="flex gap-1.5">
                    <a href="{{ route('inventaris.laporan.aset', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-secondary btn-sm">Excel</a>
                    <a href="{{ route('inventaris.laporan.aset', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-secondary btn-sm">PDF</a>
                </div>
            </div>
        @endif
        @if (Route::has('inventaris.laporan.pemeliharaan'))
            <div class="card card-pad flex items-center justify-between gap-2">
                <div><div class="font-semibold text-sm">Aset Jatuh Tempo</div><div class="text-xs text-neutral-500">Pemeliharaan H-14</div></div>
                <div class="flex gap-1.5">
                    <a href="{{ route('inventaris.laporan.pemeliharaan', ['format' => 'xlsx']) }}" class="btn btn-secondary btn-sm">Excel</a>
                    <a href="{{ route('inventaris.laporan.pemeliharaan', ['format' => 'pdf']) }}" class="btn btn-secondary btn-sm">PDF</a>
                </div>
            </div>
        @endif
    </div>

    {{-- Tabel --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Lokasi</th><th>Status</th><th>Penanggung Jawab</th></tr></thead>
                <tbody>
                    @forelse ($aset as $a)
                        <tr>
                            <td class="font-mono text-[13px] tnum text-neutral-500">{{ $a->kode }}</td>
                            <td class="font-semibold">{{ $a->nama }}</td>
                            <td class="text-[13px]">{{ $a->kategori?->nama }}</td>
                            <td class="text-[13px] text-neutral-600">{{ $a->orgUnit?->nama ?? '—' }}</td>
                            <td>{{ $a->status->label() }}</td>
                            <td class="text-[13px]">{{ $a->penanggungJawab?->nama_lengkap ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-neutral-400 py-8">Tidak ada aset.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $aset->links('livewire.sdm.partials.pager') }}
    </div>
</div>
