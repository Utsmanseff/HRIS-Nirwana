<div class="space-y-4 rise">
    <div class="flex items-center justify-between">
        <div><h1 class="text-lg font-extrabold tracking-tight">Jabatan &amp; Level</h1>
            <p class="text-sm text-neutral-500">Kelola daftar jabatan dan levelnya.</p></div>
        <button wire:click="baru" class="btn btn-primary btn-sm">+ Tambah Jabatan</button>
    </div>

    @if ($showForm)
        <div class="card card-pad space-y-3">
            <div class="card-title">{{ $editingId ? 'Ubah Jabatan' : 'Jabatan Baru' }}</div>
            <div>
                <label class="field-label">Nama Jabatan</label>
                <input wire:model="nama" class="input @error('nama') input-error @enderror" placeholder="mis. Apoteker">
                @error('nama') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Level</label>
                <select wire:model="level" class="input">
                    @foreach ($levels as $lv)
                        <option value="{{ $lv->value }}">L{{ $lv->value }} · {{ ucfirst($lv->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 pt-1">
                <button wire:click="simpan" class="btn btn-primary btn-sm">Simpan</button>
                <button wire:click="batal" class="btn btn-ghost btn-sm">Batal</button>
            </div>
        </div>
    @endif

    <div class="card">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-neutral-400 border-b border-neutral-200">
                    <th class="px-4 py-2.5 font-semibold">Jabatan</th>
                    <th class="px-4 py-2.5 font-semibold">Level</th>
                    <th class="px-4 py-2.5 font-semibold text-right">Karyawan</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jabatan as $j)
                    <tr class="border-b border-neutral-100 last:border-0">
                        <td class="px-4 py-2.5 font-semibold">{{ $j->nama }}</td>
                        <td class="px-4 py-2.5"><span class="badge badge-neutral">L{{ $j->level->value }} · {{ ucfirst($j->level->name) }}</span></td>
                        <td class="px-4 py-2.5 text-right font-mono">{{ $j->karyawan_count }}</td>
                        <td class="px-4 py-2.5 text-right"><button wire:click="edit({{ $j->id }})" class="btn btn-ghost btn-sm">Ubah</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
