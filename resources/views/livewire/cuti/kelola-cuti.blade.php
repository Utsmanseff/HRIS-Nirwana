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

        @if($tab==='jenis')
            <div class="card-pad space-y-4">
                <p class="text-xs text-neutral-500">Kode jenis tetap (tak bisa diubah/dihapus). Ubah label/aturan atau nonaktifkan.</p>
                <table class="table">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Potong Jatah</th><th>Lampiran</th><th>Backdate</th><th>Aktif</th><th></th></tr></thead>
                    <tbody>
                        @foreach($jenisCuti as $j)
                            <tr wire:key="jc-{{ $j->id }}">
                                <td class="font-mono text-xs">{{ $j->kode->value }}</td>
                                <td class="font-semibold">{{ $j->nama }}</td>
                                <td>{{ $j->potong_saldo ? 'Ya' : '—' }}</td>
                                <td>{{ $j->butuh_lampiran ? 'Wajib' : '—' }}</td>
                                <td>{{ $j->boleh_backdate ? 'Boleh' : '—' }}</td>
                                <td>
                                    <button wire:click="toggleAktif({{ $j->id }})" class="badge {{ $j->aktif ? 'badge-success' : 'badge-danger' }}">{{ $j->aktif ? 'Aktif' : 'Nonaktif' }}</button>
                                </td>
                                <td class="text-right"><button wire:click="editJenis({{ $j->id }})" class="btn btn-ghost btn-sm">Ubah</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($jcId)
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 space-y-3">
                        <div class="font-semibold text-sm">Ubah Jenis Cuti</div>
                        <div><label class="field-label">Nama</label><input wire:model="jcNama" class="input"></div>
                        <div><label class="field-label">Efek penggajian (opsional)</label><input wire:model="jcEfek" class="input" placeholder="mis. potong gaji & jasa"></div>
                        @error('jcNama') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                        <div class="flex flex-wrap gap-4 text-sm">
                            <label class="flex items-center gap-2"><input type="checkbox" wire:model="jcPotongSaldo"> Potong jatah</label>
                            <label class="flex items-center gap-2"><input type="checkbox" wire:model="jcButuhLampiran"> Butuh lampiran</label>
                            <label class="flex items-center gap-2"><input type="checkbox" wire:model="jcBolehBackdate"> Boleh backdate</label>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="simpanJenis" class="btn btn-primary btn-sm">Simpan</button>
                            <button wire:click="$set('jcId', null)" class="btn btn-secondary btn-sm">Batal</button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
