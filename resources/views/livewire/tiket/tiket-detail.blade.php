<div class="max-w-[1100px] mx-auto space-y-6 rise">
    <div>
        <a href="{{ route('tiket') }}" class="text-sm text-neutral-500 hover:underline">← Antrian</a>
    </div>

    {{-- Header --}}
    <div class="card card-pad">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="font-mono text-sm text-neutral-400">{{ $tiket->nomor }}</span>
                    <span class="badge {{ $tiket->status->badge() }}"><span class="dot"></span>{{ $tiket->status->label() }}</span>
                    <span class="badge {{ $tiket->prioritas->badge() }}">{{ $tiket->prioritas->label() }}</span>
                    <span class="badge badge-neutral">Tim {{ $tiket->tim->label() }}</span>
                </div>
                <h2 class="text-xl font-extrabold tracking-tight mt-2">{{ $tiket->judul }}</h2>
                <p class="text-sm text-neutral-500 mt-0.5">
                    {{ $tiket->jenis->label() }}
                    @if ($tiket->pelapor) · dilaporkan untuk {{ $tiket->pelapor->nama_lengkap }}@if($tiket->unit_pelapor) ({{ $tiket->unit_pelapor }})@endif
                    @else · kerja internal tim @endif
                </p>
            </div>
            @if ($anggotaTim && in_array($tiket->status, \App\Enums\StatusTiket::aktif(), true))
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary" x-on:click="$store.konfirmasi.buka({ judul: 'Batalkan tiket ini?', pesan: 'Tiket akan ditandai batal dan tidak bisa diproses lagi.', varian: 'primary', labelYa: 'Batalkan', onConfirm: () => $wire.batalkan() })">Batalkan</button>
                    @if ($tiket->status === \App\Enums\StatusTiket::Baru)
                        <button class="btn btn-primary" wire:click="mulai">Mulai Proses</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header"><div class="card-title">Detail</div></div>
                <div class="card-pad grid sm:grid-cols-2 gap-y-5 gap-x-4">
                    <div><div class="text-xs text-neutral-400">Jenis</div><div class="text-sm font-semibold mt-0.5">{{ $tiket->jenis->label() }}</div></div>
                    <div><div class="text-xs text-neutral-400">Dicatat oleh</div><div class="text-sm font-semibold mt-0.5">{{ $tiket->dibuatOleh?->name }}</div></div>
                    <div class="sm:col-span-2"><div class="text-xs text-neutral-400">Deskripsi</div><div class="text-sm mt-0.5 whitespace-pre-line">{{ $tiket->deskripsi }}</div></div>
                </div>
            </div>

            {{-- Aset tertaut / taut aset (tim) --}}
            @if ($tiket->aset)
                <div class="card">
                    <div class="card-header"><div class="card-title">Aset Tertaut</div>
                        <div class="flex gap-1.5">
                            <a href="{{ route('inventaris.detail', $tiket->aset) }}" class="btn btn-ghost btn-sm">Buka aset</a>
                            @if ($anggotaTim && in_array($tiket->status, \App\Enums\StatusTiket::aktif(), true))
                                <button class="btn btn-ghost btn-sm text-danger-600" x-on:click="$store.konfirmasi.buka({ judul: 'Lepas taut aset?', pesan: 'Aset akan dilepas dari tiket ini.', varian: 'danger', labelYa: 'Lepas', onConfirm: () => $wire.lepasAsetTaut() })">Lepas</button>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('inventaris.detail', $tiket->aset) }}" class="card-pad flex items-center gap-3 hover:bg-neutral-50 transition">
                        <span class="w-11 h-11 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><x-icon name="box" :size="20" /></span>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">{{ $tiket->aset->nama }} <span class="font-mono text-xs text-neutral-400">{{ $tiket->aset->kode }}</span></div>
                            <div class="text-xs text-neutral-400">Lokasi: {{ $tiket->aset->orgUnit?->nama ?? '—' }} · Status: {{ $tiket->aset->status->label() }}</div>
                        </div>
                    </a>
                    <div class="px-5 pb-4 text-xs text-neutral-400">Riwayat perbaikan aset muncul otomatis dari tiket ini (tanpa input ganda).</div>
                </div>
            @elseif ($anggotaTim && in_array($tiket->status, \App\Enums\StatusTiket::aktif(), true))
                <div class="card">
                    <div class="card-header"><div class="card-title">Tautkan Aset</div></div>
                    <div class="card-pad space-y-2">
                        <p class="text-xs text-neutral-400">Tiket ini belum terkait aset. Cari aset Tim {{ $tiket->tim->label() }} untuk menautkan (status aset ikut tersinkron).</p>
                        <input class="input" wire:model.live.debounce.300ms="cariAset" placeholder="Cari kode / nama aset…">
                        @if (count($asetOpsi))
                            <div class="border border-neutral-200 rounded-lg divide-y divide-neutral-100">
                                @foreach ($asetOpsi as $a)
                                    <button type="button" wire:click="tautAset({{ $a['id'] }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-neutral-50">{{ $a['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Selesaikan (anggota tim, tiket aktif) --}}
            @if ($anggotaTim && in_array($tiket->status, \App\Enums\StatusTiket::aktif(), true))
                <div class="card">
                    <div class="card-header"><div class="card-title">Selesaikan Tiket</div></div>
                    <div class="card-pad space-y-3">
                        <textarea class="textarea" rows="2" wire:model="catatanSelesai" placeholder="Catatan penyelesaian…"></textarea>
                        <button class="btn btn-primary" wire:click="selesaikan">Tandai Selesai</button>
                    </div>
                </div>
            @elseif ($tiket->catatan_penyelesaian)
                <div class="card">
                    <div class="card-header"><div class="card-title">Penyelesaian</div></div>
                    <div class="card-pad text-sm whitespace-pre-line">{{ $tiket->catatan_penyelesaian }}
                        <div class="text-xs text-neutral-400 mt-2">oleh {{ $tiket->penyelesai?->name }} · {{ $tiket->waktu_selesai?->translatedFormat('j M Y H:i') }}</div>
                    </div>
                </div>
            @endif

            {{-- Lampiran --}}
            <div class="card">
                <div class="card-header"><div class="card-title">Lampiran</div></div>
                <div class="card-pad space-y-3">
                    @forelse ($tiket->lampiran as $l)
                        <a href="{{ route('tiket.lampiran', $l) }}" target="_blank" class="block text-sm text-brand-600 hover:underline">
                            {{ $l->mime === 'application/pdf' ? '📄 Berkas PDF' : '🖼️ Gambar' }} #{{ $loop->iteration }}
                        </a>
                    @empty
                        <p class="text-sm text-neutral-400">Belum ada lampiran.</p>
                    @endforelse
                    @if ($anggotaTim || $tiket->pelapor_id === auth()->user()->karyawan_id)
                        <div class="pt-2 border-t border-neutral-100">
                            <input type="file" wire:model="berkas" class="text-sm">
                            @error('berkas')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror
                            <button class="btn btn-secondary btn-sm mt-2" wire:click="simpanLampiran">Unggah</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Side: status + metrik --}}
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><div class="card-title">Status</div></div>
                <div class="card-pad space-y-4 text-sm">
                    <div class="flex justify-between"><span class="text-neutral-400">Lapor</span><span class="tnum">{{ $tiket->waktu_lapor->translatedFormat('j M, H:i') }}</span></div>
                    <div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-400">Respon</span>
                            <span class="flex items-center gap-2">
                                <span class="tnum">{{ $tiket->waktu_respon?->translatedFormat('j M, H:i') ?? '—' }}</span>
                                @if ($anggotaTim && $tiket->waktu_respon && ! $editRespon)
                                    <button class="text-xs text-brand-600 hover:underline" wire:click="mulaiEditRespon">koreksi</button>
                                @endif
                            </span>
                        </div>
                        @if ($editRespon)
                            <div class="mt-2 space-y-2">
                                <input type="datetime-local" class="input" wire:model="waktuResponInput">
                                @error('waktuResponInput')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
                                <div class="flex gap-2">
                                    <button class="btn btn-primary btn-sm" wire:click="simpanWaktuRespon">Simpan</button>
                                    <button class="btn btn-secondary btn-sm" wire:click="batalEditRespon">Batal</button>
                                </div>
                                <p class="text-[11px] text-neutral-400">Untuk koreksi bila tiket dikerjakan lebih awal (lupa klik proses).</p>
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-between"><span class="text-neutral-400">Selesai</span><span class="tnum">{{ $tiket->waktu_selesai?->translatedFormat('j M, H:i') ?? '—' }}</span></div>
                    <div class="pt-3 border-t border-neutral-100 text-[11px] text-neutral-400">Tanpa reopen — bila masalah kambuh, buat tiket baru.</div>
                </div>
            </div>

            @if ($tiket->menitRespon() !== null || $tiket->menitPenyelesaian() !== null)
                <div class="card">
                    <div class="card-header"><div class="card-title">Metrik Waktu</div></div>
                    <div class="card-pad grid grid-cols-2 gap-2 text-center">
                        <div class="rounded-lg bg-neutral-50 py-3"><div class="text-[10px] text-neutral-400 font-semibold">RESPON</div><div class="font-bold tnum">{{ $tiket->menitRespon() !== null ? $tiket->menitRespon().' mnt' : '—' }}</div></div>
                        <div class="rounded-lg bg-neutral-50 py-3"><div class="text-[10px] text-neutral-400 font-semibold">SELESAI</div><div class="font-bold tnum">{{ $tiket->menitPenyelesaian() !== null ? $tiket->menitPenyelesaian().' mnt' : '—' }}</div></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
