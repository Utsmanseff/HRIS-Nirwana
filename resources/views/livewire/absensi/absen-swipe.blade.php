<div class="max-w-md mx-auto"
     x-data="absenSwipe({
        officeLat: {{ $pengaturan->office_lat }},
        officeLong: {{ $pengaturan->office_long }},
        radius: {{ $pengaturan->radius_m }},
        maxAkurasi: {{ $pengaturan->max_akurasi_m }},
     })">

    {{-- Jam + shift (ringkas) --}}
    <div class="text-center mb-3">
        <div class="text-3xl font-extrabold tnum tracking-tight leading-none" x-text="jam">--:--</div>
        <div class="text-xs text-neutral-500 mt-1">{{ now()->translatedFormat('l, d F Y') }}</div>
        @php($terpilihId = $this->jadwalTerpilih?->id)
        @forelse ($this->jadwalHariIni as $j)
            @php($s = $j->shift)
            <div class="inline-flex items-center gap-2 mt-2 mx-0.5 px-3 py-1 rounded-full text-xs font-semibold"
                 wire:key="shift-hari-ini-{{ $j->id }}"
                 @if ($j->id === $terpilihId)
                     style="background:var(--brand-50);color:var(--brand-700)"
                 @else
                     style="background:var(--bg-muted);color:var(--text-muted)"
                 @endif>
                {{ $s->nama }} ·
                {{ \Illuminate\Support\Str::of($s->jam_mulai)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($s->jam_selesai)->substr(0, 5) }}
                @if ($j->id === $terpilihId)
                    · toleransi {{ $s->toleransi_telat }}m
                @elseif (in_array($j->shift_id, $this->shiftTerpakai))
                    · selesai
                @endif
            </div>
        @empty
            <div class="mt-2 text-xs text-neutral-400">Tidak ada shift terjadwal hari ini (mode catat)</div>
        @endforelse
        @if ($this->jadwalHariIni->isNotEmpty() && ! $terpilihId)
            <div class="mt-2 text-xs text-neutral-400">Semua shift hari ini sudah terpakai — absen berikutnya masuk mode catat.</div>
        @endif
    </div>

    {{-- Viewfinder kamera + cek overlay --}}
    <div class="viewfinder mb-3">
        <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
        <div class="face-guide"></div>
        {{-- badge atas: status wajah + kamera --}}
        <div class="absolute top-3 left-3 right-3 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold text-white backdrop-blur"
                  :style="wajahAda ? 'background:rgba(22,163,74,.9)' : 'background:rgba(220,38,38,.9)'"
                  x-text="wajahAda ? 'Wajah terdeteksi' : 'Wajah tak terdeteksi'"></span>
            <span class="px-2.5 py-1 rounded-full bg-black/40 text-white/90 text-[11px] font-semibold backdrop-blur">Kamera depan</span>
        </div>
        {{-- overlay bawah: status radius/lokasi --}}
        <div class="absolute bottom-3 left-3 right-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold text-white backdrop-blur w-full justify-center"
                  :style="dalamRadius ? 'background:rgba(22,163,74,.85)' : 'background:rgba(0,0,0,.5)'"
                  x-text="lokasiTeks"></span>
        </div>
    </div>

    {{-- Stepper masuk → pulang (tipis) --}}
    <div class="flex items-center gap-2 mb-3 px-1">
        <div class="flex-1 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full grid place-items-center text-[10px] font-bold {{ $this->aksi === 'masuk' ? 'bg-brand-600 text-white' : 'bg-success-500 text-white' }}">1</span>
            <span class="text-xs font-semibold text-neutral-700">Masuk</span>
        </div>
        <div class="flex-1 h-0.5 bg-neutral-200"></div>
        <div class="flex-1 flex items-center gap-2 justify-end">
            <span class="text-xs font-semibold {{ $this->aksi === 'pulang' ? 'text-neutral-700' : 'text-neutral-400' }}">Pulang</span>
            <span class="w-6 h-6 rounded-full grid place-items-center text-[10px] font-bold {{ $this->aksi === 'pulang' ? 'bg-brand-600 text-white' : 'bg-neutral-100 text-neutral-400' }}">2</span>
        </div>
    </div>

    {{-- Tombol absen (langsung di bawah kamera — tak perlu scroll) --}}
    <button type="button"
            class="btn btn-primary w-full !py-4 text-base mb-2"
            :disabled="!bolehAbsen"
            @click="ambil()">
        <span x-show="!mengirim">{{ $this->aksi === 'masuk' ? 'Absen Masuk' : 'Absen Pulang' }}</span>
        <span x-show="mengirim" x-cloak>Mengirim…</span>
    </button>

    @if (session('absen_ok'))
        <p class="text-xs text-success-700 text-center mb-2">{{ session('absen_ok') }}</p>
    @endif
    @error('sesi') <p class="text-xs text-danger-600 text-center mb-2">{{ $message }}</p> @enderror
    @error('foto') <p class="text-xs text-danger-600 text-center mb-2">{{ $message }}</p> @enderror
    @error('lat')  <p class="text-xs text-danger-600 text-center mb-2">{{ $message }}</p> @enderror
    @error('akurasi') <p class="text-xs text-danger-600 text-center mb-2">{{ $message }}</p> @enderror

    {{-- Peta lokasi (sekunder — di bawah tombol) --}}
    <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider mb-2">Peta Lokasi</div>
    <div x-ref="peta" class="w-full h-40 rounded-lg overflow-hidden mb-1 bg-neutral-100"></div>
    <p class="text-[11px] text-neutral-400 mb-5">
        Titik hijau = kantor + radius. Titik biru = posisi Anda.
        Peta butuh internet (ubin online); cek radius tetap jalan tanpa peta.
    </p>

    {{-- Riwayat 7 hari --}}
    <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider mb-2.5">Riwayat 7 Hari</div>
    <div class="space-y-2">
        @forelse ($this->riwayat as $a)
            <div class="card card-pad !p-3 flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-neutral-100 grid place-items-center text-center leading-none">
                    <span class="block text-[15px] font-extrabold tnum">{{ $a->tanggal_kerja->format('d') }}</span>
                    <span class="block text-[9px] text-neutral-400 font-semibold">{{ strtoupper($a->tanggal_kerja->translatedFormat('M')) }}</span>
                </span>
                <div class="flex-1">
                    <div class="text-sm font-semibold">
                        {{ $a->jam_masuk->format('H:i') }} →
                        @if ($a->jam_pulang) {{ $a->jam_pulang->format('H:i') }}
                        @else <span class="text-danger-600">—</span> @endif
                    </div>
                    <div class="text-xs text-neutral-400">
                        @if ($a->totalMenit()) {{ intdiv($a->totalMenit(), 60) }}j {{ $a->totalMenit() % 60 }}m @endif
                        {{ $a->shift_nama ? '· '.$a->shift_nama : '' }}
                    </div>
                </div>
                @if ($a->foto_masuk_path)
                    <a href="{{ route('absensi.foto', [$a->id, 'masuk']) }}" target="_blank"
                       class="w-9 h-9 rounded-md overflow-hidden bg-neutral-100 shrink-0" title="Foto masuk">
                        <img src="{{ route('absensi.foto', [$a->id, 'masuk']) }}" alt="Foto masuk" class="w-full h-full object-cover" loading="lazy">
                    </a>
                @endif
                @if ($a->anomali())
                    <span class="badge badge-danger">Anomali</span>
                @elseif ($a->telat_menit)
                    <span class="badge badge-warning">Telat {{ $a->telat_menit }}m</span>
                @else
                    <span class="badge badge-success">Normal</span>
                @endif
            </div>
        @empty
            <div class="text-sm text-neutral-400 text-center py-6">Belum ada riwayat absen.</div>
        @endforelse
    </div>
</div>
