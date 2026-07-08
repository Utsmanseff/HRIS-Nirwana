<div class="max-w-4xl mx-auto space-y-4 rise">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-extrabold tracking-tight">{{ $aset ? 'Ubah Aset' : 'Tambah Aset' }}</h1>
        <a href="{{ route('inventaris') }}" class="btn btn-secondary btn-sm">← Kembali</a>
    </div>

    <div class="card p-4 space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-semibold text-neutral-500">Kode (asset tag)</label>
                <input wire:model="kode" class="input" placeholder="mis. IT-0001">
                @error('kode') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Nama</label>
                <input wire:model="nama" class="input" placeholder="mis. PC Front Office">
                @error('nama') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Kategori</label>
                <select wire:model="kategoriId" class="select">
                    <option value="">Pilih kategori…</option>
                    @foreach ($kategoriList as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
                @error('kategoriId') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Status</label>
                <select wire:model="status" class="select">
                    @foreach ($statusOpsi as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Merk</label>
                <input wire:model="merk" class="input">
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Model</label>
                <input wire:model="model" class="input">
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">No. Seri</label>
                <input wire:model="noSeri" class="input">
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Tanggal Pengadaan</label>
                <input type="date" wire:model="tanggalPengadaan" class="input">
                @error('tanggalPengadaan') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Nilai Perolehan (Rp)</label>
                <input type="number" wire:model="nilaiPerolehan" class="input">
                @error('nilaiPerolehan') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Lokasi (Unit)</label>
                <select wire:model="orgUnitId" class="select">
                    <option value="">— Belum ditempatkan —</option>
                    @foreach ($unitList as $u)
                        <option value="{{ $u->id }}">{{ $u->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-semibold text-neutral-500">Keterangan</label>
                <textarea wire:model="keterangan" class="input" rows="3"></textarea>
            </div>
        </div>
        <div class="flex items-center gap-2 pt-2 border-t border-neutral-100">
            <button wire:click="simpan" class="btn btn-primary btn-sm">Simpan</button>
        </div>
    </div>
</div>
