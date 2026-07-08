<div class="space-y-4 rise" x-data="{ tab: @entangle('tab').live }">
    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div class="flex items-start gap-3">
            <a href="{{ route('inventaris') }}" class="btn btn-ghost btn-sm mt-0.5">←</a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-[13px] tnum text-neutral-500">{{ $aset->kode }}</span>
                    @php $kelas = match($aset->status) {
                        \App\Enums\StatusAset::Baik => 'badge-success',
                        \App\Enums\StatusAset::Rusak => 'badge-danger',
                        \App\Enums\StatusAset::DalamPerbaikan => 'badge-warning',
                        \App\Enums\StatusAset::Afkir => 'badge-neutral',
                    }; @endphp
                    <span class="badge {{ $kelas }}">{{ $aset->status->label() }}</span>
                </div>
                <h1 class="text-lg font-extrabold tracking-tight">{{ $aset->nama }}</h1>
                <p class="text-sm text-neutral-500">{{ $aset->kategori->nama }} · Tim {{ $aset->kategori->tim->label() }}</p>
            </div>
        </div>
        <a href="{{ route('inventaris.ubah', $aset) }}" class="btn btn-secondary btn-sm">Ubah Aset</a>
    </div>

    @if (session('ok'))
        <div class="card p-3 text-sm" style="border-color:var(--success-500)">{{ session('ok') }}</div>
    @endif

    {{-- Tab bar --}}
    <div class="flex gap-1 overflow-x-auto border-b border-neutral-100">
        @foreach (['info' => 'Info', 'riwayat' => 'Riwayat Perbaikan', 'jadwal' => 'Jadwal', 'mutasi' => 'Mutasi', 'lampiran' => 'Lampiran'] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'tab-btn on' : 'tab-btn'"
                class="whitespace-nowrap shrink-0">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Info --}}
    <div x-show="tab === 'info'" class="card p-4">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            <div><dt class="text-xs font-semibold text-neutral-500">Merk</dt><dd>{{ $aset->merk ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold text-neutral-500">Model</dt><dd>{{ $aset->model ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold text-neutral-500">No. Seri</dt><dd class="font-mono text-[13px]">{{ $aset->no_seri ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold text-neutral-500">Lokasi</dt><dd>{{ $aset->orgUnit?->nama ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold text-neutral-500">Penanggung Jawab</dt><dd>{{ $aset->penanggungJawab?->nama_lengkap ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold text-neutral-500">Tgl Pengadaan</dt><dd>{{ $aset->tanggal_pengadaan?->format('d M Y') ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold text-neutral-500">Nilai Perolehan</dt><dd>{{ $aset->nilai_perolehan ? 'Rp '.number_format((float) $aset->nilai_perolehan, 0, ',', '.') : '—' }}</dd></div>
            <div class="sm:col-span-2 lg:col-span-3"><dt class="text-xs font-semibold text-neutral-500">Keterangan</dt><dd>{{ $aset->keterangan ?? '—' }}</dd></div>
        </dl>
    </div>

    {{-- Riwayat Perbaikan (derived dari tiket inventaris_id) --}}
    <div x-show="tab === 'riwayat'" class="card" x-cloak>
        @if ($riwayatTiket->isEmpty())
            <p class="card-pad text-sm text-neutral-400 text-center">Belum ada tiket untuk aset ini.</p>
        @else
            <div class="divide-y divide-neutral-100">
                @foreach ($riwayatTiket as $t)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('tiket.detail') ? route('tiket.detail', $t) : '#' }}"
                       class="flex items-start justify-between gap-3 p-4 hover:bg-neutral-50 transition">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-xs text-neutral-400">{{ $t->nomor }}</span>
                                <span class="badge {{ $t->jenis === \App\Enums\JenisTiket::Pemeliharaan ? 'badge-info' : 'badge-neutral' }} text-[10px]">{{ $t->jenis->label() }}</span>
                            </div>
                            <div class="text-sm font-semibold mt-1 truncate">{{ $t->judul }}</div>
                            <div class="text-xs text-neutral-400 mt-0.5">{{ $t->waktu_lapor->translatedFormat('j M Y') }}@if($t->penyelesai) · oleh {{ $t->penyelesai->name }}@endif</div>
                        </div>
                        <span class="badge {{ $t->status->badge() }} shrink-0"><span class="dot"></span>{{ $t->status->label() }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Jadwal Pemeliharaan --}}
    <div x-show="tab === 'jadwal'" class="grid gap-4 md:grid-cols-2" x-cloak>
        <div class="card p-4 space-y-3 h-fit">
            <h2 class="font-semibold text-sm">{{ $editJadwalId ? 'Ubah Jadwal' : 'Tambah Jadwal' }}</h2>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Nama</label>
                <input wire:model="jNama" class="input" placeholder="mis. Kalibrasi">
                @error('jNama') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Interval (bulan)</label>
                <input type="number" min="1" wire:model="jInterval" class="input" placeholder="6">
                @error('jInterval') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="simpanJadwal" class="btn btn-primary btn-sm">Simpan</button>
                @if ($editJadwalId)
                    <button wire:click="batalJadwal" class="btn btn-secondary btn-sm">Batal</button>
                @endif
            </div>
        </div>

        <div class="card divide-y divide-neutral-100">
            @forelse ($aset->jadwalPemeliharaan as $j)
                <div class="p-3 flex items-center justify-between gap-2">
                    <div>
                        <div class="font-semibold text-sm">{{ $j->nama }}</div>
                        <div class="text-xs text-neutral-400">
                            Tiap {{ $j->interval_bulan }} bln ·
                            Terakhir: {{ $j->terakhir_dilakukan?->format('d M Y') ?? 'belum pernah' }} ·
                            Berikutnya: {{ $j->berikutnya()?->format('d M Y') ?? '—' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button wire:click="tandaiJadwalSelesai({{ $j->id }})" class="btn btn-ghost btn-sm">Tandai Selesai</button>
                        <button wire:click="editJadwal({{ $j->id }})" class="btn btn-ghost btn-sm">Ubah</button>
                        <button wire:click="hapusJadwal({{ $j->id }})" wire:confirm="Hapus jadwal ini?" class="btn btn-ghost btn-sm" style="color:var(--danger-500)">Hapus</button>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-neutral-400 text-sm">Belum ada jadwal pemeliharaan.</div>
            @endforelse
        </div>
    </div>

    {{-- Mutasi --}}
    <div x-show="tab === 'mutasi'" class="grid gap-4 md:grid-cols-2" x-cloak>
        <div class="card p-4 space-y-3 h-fit">
            <h2 class="font-semibold text-sm">Pindahkan Unit</h2>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Unit Tujuan</label>
                <select wire:model="mutasiUnitId" class="select">
                    <option value="">Pilih unit…</option>
                    @foreach ($unitList as $u)
                        <option value="{{ $u->id }}">{{ $u->nama }}</option>
                    @endforeach
                </select>
                @error('mutasiUnitId') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Catatan</label>
                <input wire:model="mutasiCatatan" class="input" placeholder="opsional">
            </div>
            <button wire:click="simpanMutasi" class="btn btn-primary btn-sm">Simpan Mutasi</button>
        </div>

        <div class="card divide-y divide-neutral-100">
            @forelse ($aset->mutasi as $m)
                <div class="p-3 text-sm">
                    <div class="flex items-center gap-2 text-xs text-neutral-400">
                        <span>{{ $m->tanggal?->format('d M Y') }}</span>
                        <span>·</span>
                        <span>{{ $m->oleh?->name ?? '—' }}</span>
                    </div>
                    <div class="font-medium">{{ $m->dariUnit?->nama ?? '—' }} → {{ $m->keUnit?->nama ?? '—' }}</div>
                    @if ($m->catatan)
                        <div class="text-xs text-neutral-500">{{ $m->catatan }}</div>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-neutral-400 text-sm">Belum ada mutasi.</div>
            @endforelse
        </div>
    </div>

    {{-- Lampiran --}}
    <div x-show="tab === 'lampiran'" class="grid gap-4 md:grid-cols-3" x-cloak>
        <div class="card p-4 space-y-3 h-fit">
            <h2 class="font-semibold text-sm">Tambah Lampiran</h2>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Tipe</label>
                <select wire:model="lampiranTipe" class="select">
                    <option value="sertifikat">Sertifikat</option>
                    <option value="faktur">Faktur</option>
                    <option value="manual">Manual</option>
                    <option value="garansi">Garansi</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-neutral-500">Berkas (gambar/PDF, maks 8MB)</label>
                <input type="file" wire:model="berkas" accept=".jpg,.jpeg,.png,.webp,.pdf" class="input">
                <div wire:loading wire:target="berkas" class="text-xs text-neutral-400 mt-1">Mengunggah…</div>
                @error('berkas') <span class="text-xs" style="color:var(--danger-500)">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-semibold text-neutral-500">Tanggal</label>
                    <input type="date" wire:model="lampiranTanggal" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-neutral-500">Berlaku s/d</label>
                    <input type="date" wire:model="lampiranBerlakuSampai" class="input">
                </div>
            </div>
            <button wire:click="simpanLampiran" wire:loading.attr="disabled" class="btn btn-primary btn-sm">Simpan</button>
        </div>

        <div class="md:col-span-2">
            @if ($aset->lampiran->isEmpty())
                <div class="card p-6 text-center text-neutral-400 text-sm">Belum ada lampiran.</div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ($aset->lampiran as $l)
                        <div class="card p-2 space-y-2">
                            <a href="{{ route('inventaris.lampiran', $l) }}" target="_blank" class="block">
                                @if ($l->mime === 'image/webp')
                                    <img src="{{ route('inventaris.lampiran', $l) }}" alt="{{ $l->tipe }}" class="w-full h-24 object-cover rounded-lg bg-neutral-100">
                                @else
                                    <div class="w-full h-24 rounded-lg bg-neutral-100 flex items-center justify-center text-neutral-400 text-xs font-semibold">PDF</div>
                                @endif
                            </a>
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-xs font-semibold capitalize">{{ $l->tipe }}</span>
                                <button wire:click="hapusLampiran({{ $l->id }})" wire:confirm="Hapus lampiran ini?" class="btn btn-ghost btn-sm" style="color:var(--danger-500)">×</button>
                            </div>
                            @if ($l->berlaku_sampai)
                                <div class="text-[11px] text-neutral-400">Berlaku s/d {{ $l->berlaku_sampai->format('d M Y') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
