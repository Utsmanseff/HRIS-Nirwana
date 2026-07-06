<div class="mx-auto max-w-4xl">
    <h1 class="text-xl font-bold mb-4">Kelola Cuti</h1>
    <div class="card">
        <div class="px-4 flex gap-1 border-b border-neutral-100 overflow-x-auto whitespace-nowrap">
            <button wire:click="$set('tab','hari-libur')" class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='hari-libur' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">Hari Libur</button>
            <button wire:click="$set('tab','jenis')" class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='jenis' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">Jenis Cuti</button>
            <button wire:click="$set('tab','penyesuaian')" class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='penyesuaian' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">Penyesuaian Jatah</button>
        </div>
        @if($tab==='hari-libur')
            <div class="card-pad space-y-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div><label class="field-label">Tanggal</label><input type="date" wire:model="hlTanggal" class="input w-auto"></div>
                    <div class="flex-1 min-w-[200px]"><label class="field-label">Nama</label><input wire:model="hlNama" class="input" placeholder="mis. Cuti Bersama Idul Fitri"></div>
                    <button wire:click="simpanHariLibur" class="btn btn-primary">{{ $editHlId ? 'Simpan' : 'Tambah' }}</button>
                    @if($editHlId)<button wire:click="resetHariLibur" class="btn btn-secondary">Batal</button>@endif
                </div>
                @error('hlTanggal') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                @error('hlNama') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror

                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Nama</th><th></th></tr></thead>
                    <tbody>
                        @forelse($hariLibur as $h)
                            <tr wire:key="hl-{{ $h->id }}">
                                <td class="tnum">{{ $h->tanggal->format('d M Y') }}</td>
                                <td>{{ $h->nama }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <button wire:click="editHariLibur({{ $h->id }})" class="btn btn-ghost btn-sm">Ubah</button>
                                    <button wire:click="hapusHariLibur({{ $h->id }})" class="btn btn-ghost btn-sm text-danger-600">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-sm text-neutral-500">Belum ada hari libur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
