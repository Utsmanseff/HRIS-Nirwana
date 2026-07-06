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

        @if($tab==='penyesuaian')
            <div class="card-pad space-y-4">
                @if(! $karyawanTerpilih)
                    <div>
                        <label class="field-label">Cari karyawan</label>
                        <input wire:model.live.debounce.400ms="psCari" class="input" placeholder="Nama / NIP…">
                    </div>
                    @if($hasilCari->isNotEmpty())
                        <div class="border border-neutral-200 rounded-lg divide-y">
                            @foreach($hasilCari as $k)
                                <button wire:click="pilihKaryawan({{ $k->id }})" class="w-full text-left px-3 py-2 hover:bg-neutral-50">
                                    <span class="font-semibold">{{ $k->nama_lengkap }}</span> <span class="text-xs text-neutral-400 font-mono">{{ $k->nip }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex items-center justify-between">
                        <div><span class="font-semibold">{{ $karyawanTerpilih->nama_lengkap }}</span> <span class="text-xs text-neutral-400 font-mono">{{ $karyawanTerpilih->nip }}</span></div>
                        <button wire:click="batalKaryawan" class="btn btn-ghost btn-sm">Ganti</button>
                    </div>

                    @if(empty($periodeOpsi))
                        <div class="rounded-lg bg-warning-50 border border-warning-100 p-3 text-sm text-warning-700">Karyawan belum berhak jatah (masa kerja &lt; 1 tahun).</div>
                    @else
                        <div class="flex flex-wrap items-end gap-3">
                            <div><label class="field-label">Periode</label>
                                <select wire:model="psPeriode" class="select w-auto">
                                    <option value="">Pilih periode…</option>
                                    @foreach($periodeOpsi as $pd)<option value="{{ $pd }}">{{ \Illuminate\Support\Carbon::parse($pd)->format('d M Y') }}</option>@endforeach
                                </select>
                            </div>
                            <div><label class="field-label">Delta (± hari)</label><input type="number" wire:model="psDelta" class="input w-24" placeholder="mis. 3 / -2"></div>
                            <div class="flex-1 min-w-[200px]"><label class="field-label">Alasan</label><input wire:model="psAlasan" class="input" placeholder="mis. bonus loyalitas"></div>
                            <button wire:click="simpanPenyesuaian" class="btn btn-primary">Simpan</button>
                        </div>
                        @error('psPeriode') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                        @error('psDelta') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                        @error('psAlasan') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                    @endif

                    <table class="table">
                        <thead><tr><th>Periode</th><th>Delta</th><th>Alasan</th><th>Oleh</th><th></th></tr></thead>
                        <tbody>
                            @forelse($penyesuaian as $ps)
                                <tr wire:key="ps-{{ $ps->id }}">
                                    <td class="tnum">{{ $ps->periode_mulai->format('d M Y') }}</td>
                                    <td class="tnum font-semibold {{ $ps->delta < 0 ? 'text-danger-600' : 'text-success-600' }}">{{ $ps->delta > 0 ? '+' : '' }}{{ $ps->delta }}</td>
                                    <td>{{ $ps->alasan }}</td>
                                    <td class="text-xs text-neutral-400">{{ $ps->pembuat?->name ?? '—' }}</td>
                                    <td class="text-right"><button wire:click="hapusPenyesuaian({{ $ps->id }})" class="btn btn-ghost btn-sm text-danger-600">Hapus</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-sm text-neutral-500">Belum ada penyesuaian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>
</div>
