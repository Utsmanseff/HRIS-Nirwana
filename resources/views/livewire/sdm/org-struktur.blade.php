<div class="space-y-4 rise">
    <div class="flex items-center justify-between">
        <div><h1 class="text-lg font-extrabold tracking-tight">Struktur Organisasi</h1>
            <p class="text-sm text-neutral-500 max-w-xl">Pohon unit organisasi (Direktur → Bidang/Bagian → Unit). Terpisah dari jabatan.</p></div>
        <button wire:click="baru" class="btn btn-primary btn-sm">+ Tambah Unit</button>
    </div>

    @if ($showForm)
        <div class="card card-pad space-y-3">
            <div class="card-title">{{ $editingId ? 'Ubah Unit' : 'Unit Baru' }}</div>
            <div>
                <label class="field-label">Nama Unit</label>
                <input wire:model="nama" class="input @error('nama') input-error @enderror" placeholder="mis. Unit Farmasi">
                @error('nama') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="field-label">Tipe</label>
                    <select wire:model="tipe" class="input">
                        @foreach ($tipeOptions as $t)
                            <option value="{{ $t->value }}">{{ ucfirst($t->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Induk (opsional)</label>
                    <select wire:model="parentId" class="input">
                        <option value="">— Akar (tanpa induk) —</option>
                        @foreach ($semuaUnit as $u)
                            @if ($u->id !== $editingId)
                                <option value="{{ $u->id }}">{{ $u->nama }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex gap-2 pt-1">
                <button wire:click="simpan" class="btn btn-primary btn-sm">Simpan</button>
                <button wire:click="batal" class="btn btn-ghost btn-sm">Batal</button>
            </div>
        </div>
    @endif

    @if ($setKepalaUnitId)
        <div class="card card-pad space-y-3">
            <div class="flex items-center justify-between">
                <div class="card-title">Set Kepala Unit</div>
                <button wire:click="tutupSetKepala" class="btn btn-ghost btn-sm">Tutup</button>
            </div>
            <div>
                <label class="field-label">Cari karyawan (nama / NIP)</label>
                <input wire:model.live.debounce.300ms="cariKaryawan" class="input" placeholder="ketik nama atau NIP…">
            </div>
            @if ($hasilCari->isNotEmpty())
                <div class="divide-y divide-neutral-100 border border-neutral-200 rounded-lg">
                    @foreach ($hasilCari as $c)
                        <div class="flex items-center justify-between px-3 py-2">
                            <div class="text-sm"><span class="font-semibold">{{ $c->nama_lengkap }}</span>
                                <span class="text-xs text-neutral-400 font-mono">{{ $c->nip }}</span></div>
                            <button wire:click="pilihKepala({{ $c->id }})" class="btn btn-primary btn-sm">Jadikan kepala</button>
                        </div>
                    @endforeach
                </div>
            @elseif (trim($cariKaryawan) !== '')
                <p class="text-xs text-neutral-400">Tak ada hasil. Coba kata kunci lain atau tambah cepat.</p>
            @endif
            <div class="pt-2 border-t border-neutral-100 space-y-2">
                <div class="text-xs font-semibold text-neutral-500">Atau tambah karyawan baru sebagai kepala</div>
                <div class="grid sm:grid-cols-3 gap-2">
                    <div>
                        <label class="field-label">NIP *</label>
                        <input wire:model="tcNip" class="input @error('tcNip') input-error @enderror">
                        @error('tcNip') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Nama *</label>
                        <input wire:model="tcNama" class="input @error('tcNama') input-error @enderror">
                        @error('tcNama') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Tanggal Masuk</label>
                        <input type="date" wire:model="tcTanggalMasuk" class="input">
                    </div>
                </div>
                <button wire:click="tambahCepatKepala" class="btn btn-secondary btn-sm">Tambah &amp; jadikan kepala</button>
            </div>
        </div>
    @endif

    @if ($jabatanUnitId)
        <div class="card card-pad space-y-3">
            <div class="flex items-center justify-between">
                <div class="card-title">Kelola Jabatan Staff</div>
                <button wire:click="tutupJabatan" class="btn btn-ghost btn-sm">Tutup</button>
            </div>
            @if ($jabatanUnit->isNotEmpty())
                <div class="divide-y divide-neutral-100 border border-neutral-200 rounded-lg">
                    @foreach ($jabatanUnit as $j)
                        <div class="flex items-center justify-between px-3 py-2">
                            <span class="text-sm font-semibold">{{ $j->nama }}</span>
                            <button wire:click="editJabatanStaff({{ $j->id }})" class="btn btn-ghost btn-sm">Ubah</button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-neutral-400">Belum ada jabatan staff di unit ini.</p>
            @endif
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="field-label">{{ $editJabatanId ? 'Ubah nama jabatan' : 'Jabatan staff baru' }}</label>
                    <input wire:model="jNama" class="input @error('jNama') input-error @enderror" placeholder="mis. Perawat, Apoteker, CS…">
                    @error('jNama') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                </div>
                <button wire:click="simpanJabatanStaff" class="btn btn-primary btn-sm">Simpan</button>
            </div>
        </div>
    @endif

    <div class="card card-pad">
        <div class="space-y-0.5">
            @forelse ($akar as $unit)
                @include('livewire.sdm.partials.org-node', ['unit' => $unit])
            @empty
                <p class="text-sm text-neutral-400 py-4 text-center">Belum ada unit organisasi.</p>
            @endforelse
        </div>
    </div>
</div>
