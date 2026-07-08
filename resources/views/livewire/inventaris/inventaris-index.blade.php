<div class="space-y-4 rise">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div><h1 class="text-lg font-extrabold tracking-tight">Inventaris</h1>
            <p class="text-sm text-neutral-500">Daftar aset tim {{ implode(', ', array_map(fn ($t) => $t->label(), auth()->user()->timTeknis())) }}.</p></div>
        <div class="flex items-center gap-2">
            @can('kelola-inventaris')
                @if (Route::has('inventaris.kategori'))
                    <a href="{{ route('inventaris.kategori') }}" class="btn btn-secondary btn-sm">Kelola Kategori</a>
                @endif
                @if (Route::has('inventaris.laporan'))
                    <a href="{{ route('inventaris.laporan') }}" class="btn btn-secondary btn-sm">Laporan</a>
                @endif
                @if (Route::has('inventaris.tambah'))
                    <a href="{{ route('inventaris.tambah') }}" class="btn btn-primary btn-sm">+ Tambah Aset</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="p-4 flex flex-wrap items-center gap-2.5 border-b border-neutral-100">
            <div class="relative flex-1 min-w-[220px]">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"><svg width="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4" stroke-linecap="round"/></svg></span>
                <input wire:model.live.debounce.300ms="q" class="input" style="padding-left:2.35rem" placeholder="Cari nama, kode, atau no. seri…">
            </div>
            <select wire:model.live="kategoriId" class="select" style="width:auto">
                <option value="">Semua Kategori</option>
                @foreach ($kategoriList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="select" style="width:auto">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\StatusAset::cases() as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th><th>Nama</th><th>Kategori</th><th>Lokasi</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aset as $a)
                        <tr @if (Route::has('inventaris.detail')) class="table-row-link" onclick="window.location='{{ route('inventaris.detail', $a) }}'" @endif>
                            <td class="font-mono text-[13px] tnum text-neutral-500">{{ $a->kode }}</td>
                            <td class="font-semibold">{{ $a->nama }}</td>
                            <td class="text-[13px]">{{ $a->kategori->nama }}</td>
                            <td class="text-[13px] text-neutral-600">{{ $a->orgUnit?->nama ?? '—' }}</td>
                            <td>
                                @php $kelas = match($a->status) {
                                    \App\Enums\StatusAset::Baik => 'badge-success',
                                    \App\Enums\StatusAset::Rusak => 'badge-danger',
                                    \App\Enums\StatusAset::DalamPerbaikan => 'badge-warning',
                                    \App\Enums\StatusAset::Afkir => 'badge-neutral',
                                }; @endphp
                                <span class="badge {{ $kelas }}">{{ $a->status->label() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-neutral-400 py-8">Tidak ada aset.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $aset->links('livewire.sdm.partials.pager') }}
    </div>
</div>
