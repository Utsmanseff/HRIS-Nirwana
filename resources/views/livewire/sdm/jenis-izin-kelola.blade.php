<div class="max-w-3xl mx-auto">
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight">Jenis Izin</h1>
        <p class="text-neutral-500 text-sm mt-1">
            Nama tampilan &amp; ambang pengingat tiap jenis perizinan. Kode tak bisa diubah karena
            dirujuk oleh sistem.
        </p>
    </div>

    @if (session('ok'))
        <div class="mb-4 px-4 py-2.5 rounded-lg text-sm font-medium"
             style="background:var(--success-50);color:var(--success-700)">{{ session('ok') }}</div>
    @endif

    <div class="card overflow-hidden">
        <div class="divide-y divide-neutral-100">
            @foreach ($daftar as $j)
                <div class="px-4 py-3" wire:key="jenis-{{ $j->id }}">
                    @if ($editId === $j->id)
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Nama</label>
                                <input wire:model="nama" class="input @error('nama') input-error @enderror">
                                @error('nama') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="field-label">Ambang pengingat (hari)</label>
                                <input type="number" wire:model="ambangHari" class="input tnum @error('ambangHari') input-error @enderror">
                                @error('ambangHari') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-neutral-700 mt-3">
                            <input type="checkbox" wire:model="aktif" class="w-4 h-4 accent-brand-500">
                            <span>Aktif</span>
                        </label>
                        <div class="flex gap-2 mt-3">
                            <button wire:click="simpan" class="btn btn-primary btn-sm">Simpan</button>
                            <button wire:click="batal" class="btn btn-secondary btn-sm">Batal</button>
                        </div>
                    @else
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold text-sm">{{ $j->nama }}
                                    <span class="font-mono text-xs text-neutral-400">{{ $j->kode->value }}</span>
                                </div>
                                <div class="text-xs text-neutral-500">
                                    Ingatkan H-{{ $j->ambang_hari }} · {{ $j->aktif ? 'aktif' : 'nonaktif' }}
                                </div>
                            </div>
                            <button wire:click="edit({{ $j->id }})" class="btn btn-secondary btn-sm">Ubah</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
