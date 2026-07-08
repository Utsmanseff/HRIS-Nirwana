<div class="space-y-4 rise">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div><h1 class="text-lg font-extrabold tracking-tight">Kelola Kategori Inventaris</h1>
            <p class="text-sm text-neutral-500">Kategori aset per-tim.</p></div>
        <a href="{{ route('inventaris') }}" class="btn btn-secondary btn-sm">← Kembali</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="card p-4 space-y-3 h-fit">
            <h2 class="font-semibold text-sm">{{ $editId ? 'Ubah Kategori' : 'Tambah Kategori' }}</h2>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Nama</label>
                <input wire:model="nama" class="input" placeholder="mis. Laptop">
                @error('nama') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Tim</label>
                <select wire:model="tim" class="select">
                    <option value="">Pilih tim…</option>
                    @foreach ($timOpsi as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </select>
                @error('tim') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="simpan" class="btn btn-primary btn-sm">Simpan</button>
                @if ($editId)
                    <button wire:click="batal" class="btn btn-secondary btn-sm">Batal</button>
                @endif
            </div>
        </div>

        <div class="card divide-y divide-neutral-100">
            @forelse ($daftar as $k)
                <div class="p-3 flex items-center justify-between gap-2">
                    <div>
                        <div class="font-semibold text-sm">{{ $k->nama }}</div>
                        <div class="text-xs text-neutral-400">{{ $k->tim->label() }} · {{ $k->aset_count }} aset</div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="badge {{ $k->aktif ? 'badge-success' : 'badge-neutral' }}">{{ $k->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        <button wire:click="edit({{ $k->id }})" class="btn btn-ghost btn-sm">Ubah</button>
                        <button wire:click="toggleAktif({{ $k->id }})" class="btn btn-ghost btn-sm">{{ $k->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-neutral-400 text-sm">Belum ada kategori.</div>
            @endforelse
        </div>
    </div>
</div>
