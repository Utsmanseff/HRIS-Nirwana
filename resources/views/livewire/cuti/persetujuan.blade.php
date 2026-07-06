<div class="mx-auto max-w-6xl">
    <h1 class="text-xl font-bold mb-4">Persetujuan Cuti</h1>

    <div class="card">
        <div class="px-4 flex gap-1 border-b border-neutral-100">
            <button wire:click="$set('tab','perlu-aksi')"
                class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='perlu-aksi' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                Perlu Aksi Saya
                @if($perluAksi->isNotEmpty())
                    <span class="ml-1 inline-grid place-items-center w-5 h-5 rounded-full bg-warning-500 text-white text-[11px]">{{ $perluAksi->count() }}</span>
                @endif
            </button>
            @if($bolehSemua)
                <button wire:click="$set('tab','semua')"
                    class="px-4 py-3 text-sm font-medium border-b-2 {{ $tab==='semua' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                    Semua Pengajuan
                </button>
            @endif
        </div>

        @if($tab==='perlu-aksi')
            @if($perluAksi->isEmpty())
                <div class="card-pad text-sm text-neutral-500">Tak ada pengajuan yang menunggu aksi Anda.</div>
            @else
                <table class="table rtable">
                    <thead><tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Hari</th><th>Tahap</th><th></th></tr></thead>
                    <tbody>
                        @foreach($perluAksi as $p)
                            <tr wire:key="pa-{{ $p->id }}">
                                <td data-primary>
                                    <div class="font-semibold">{{ $p->karyawan->nama_lengkap }}</div>
                                    <div class="text-xs text-neutral-400">{{ $p->karyawan->jabatan?->nama }}</div>
                                </td>
                                <td data-label="Jenis">{{ $p->jenisCuti->nama }}</td>
                                <td data-label="Tanggal" class="tnum text-neutral-600">{{ $p->tanggal_mulai->format('d M') }}–{{ $p->tanggal_selesai->format('d M') }}</td>
                                <td data-label="Hari" class="tnum font-semibold">{{ $p->jumlah_hari }}</td>
                                <td data-label="Tahap">{{ ucfirst($p->tahapAktif()?->peran?->value ?? '') }}</td>
                                <td class="text-right">
                                    <button wire:click="tinjau({{ $p->id }})" class="btn btn-secondary btn-sm">Tinjau</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        @if($tab==='semua' && $bolehSemua)
            <div class="p-4 flex flex-wrap gap-2.5 border-b border-neutral-100">
                <input wire:model.live.debounce.400ms="cari" class="input flex-1 min-w-[200px]" placeholder="Cari nama / NIP…">
                <select wire:model.live="filterJenis" class="select w-auto">
                    <option value="">Semua Jenis</option>
                    @foreach($jenisOpsi as $j)<option value="{{ $j->id }}">{{ $j->nama }}</option>@endforeach
                </select>
                <select wire:model.live="filterStatus" class="select w-auto">
                    <option value="">Semua Status</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="diproses">Diproses</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <table class="table rtable">
                <thead><tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Hari</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($semua as $p)
                        <tr wire:key="sm-{{ $p->id }}">
                            <td data-primary><div class="font-semibold">{{ $p->karyawan->nama_lengkap }}</div><div class="text-xs text-neutral-400 font-mono">{{ $p->karyawan->nip }}</div></td>
                            <td data-label="Jenis">{{ $p->jenisCuti->nama }}</td>
                            <td data-label="Tanggal" class="tnum text-neutral-600">{{ $p->tanggal_mulai->format('d M') }}–{{ $p->tanggal_selesai->format('d M') }}</td>
                            <td data-label="Hari" class="tnum">{{ $p->jumlah_hari }}</td>
                            <td data-label="Status"><span class="badge">{{ ucfirst($p->status->value) }}</span></td>
                            <td class="text-right">
                                @if($p->status->value === 'disetujui')
                                    <button wire:click="mulaiBatal({{ $p->id }})" class="btn btn-secondary btn-sm text-danger-600">Batalkan</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="card-pad text-sm text-neutral-500">Tak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    {{-- Slide-over review --}}
    <div x-show="$wire.tinjauId !== null" class="fixed inset-0 z-50" x-cloak>
        <div class="absolute inset-0 bg-black/20" @click="$wire.tutup()"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white shadow-xl overflow-y-auto">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h2 class="text-lg font-bold">Tinjau Pengajuan</h2>
                <button @click="$wire.tutup()" class="btn btn-ghost btn-sm">&times;</button>
            </div>
            <div class="p-4 space-y-4">
                <div class="text-sm text-neutral-500">Detail pengajuan di sini.</div>

                <div>
                    <label class="label">Catatan</label>
                    <textarea wire:model="catatan" class="input w-full" rows="3" placeholder="Catatan (wajib saat menolak)"></textarea>
                    @error('catatan') <p class="text-xs text-error-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2 pt-2">
                    <button wire:click="setujui" class="btn btn-primary btn-sm flex-1">Setujui</button>
                    <button wire:click="tolak" class="btn btn-danger btn-sm flex-1">Tolak</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal batal HRD --}}
    @if($batalId)
        <div class="fixed inset-0 z-50 grid place-items-center p-4">
            <div class="absolute inset-0" style="background:rgba(8,12,11,.55)" wire:click="$set('batalId', null)"></div>
            <div class="relative card card-pad w-full max-w-sm space-y-3">
                <div class="font-bold">Batalkan Cuti Disetujui</div>
                <p class="text-xs text-neutral-500">Jatah cuti tahunan akan otomatis kembali. Pemohon diberi tahu.</p>
                <textarea wire:model="alasanBatal" class="textarea" rows="2" placeholder="Alasan pembatalan…"></textarea>
                @error('alasanBatal') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                <div class="flex gap-2 justify-end">
                    <button class="btn btn-secondary btn-sm" wire:click="$set('batalId', null)">Batal</button>
                    <button class="btn btn-primary btn-sm" wire:click="konfirmasiBatal">Konfirmasi</button>
                </div>
            </div>
        </div>
    @endif
</div>
