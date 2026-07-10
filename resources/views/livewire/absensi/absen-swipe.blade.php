<div class="max-w-md mx-auto"
     x-data="absenSwipe({
        officeLat: {{ $pengaturan->office_lat }},
        officeLong: {{ $pengaturan->office_long }},
        radius: {{ $pengaturan->radius_m }},
        maxAkurasi: {{ $pengaturan->max_akurasi_m }},
     })">

    {{-- Jam + shift --}}
    <div class="text-center mb-4">
        <div class="text-4xl font-extrabold tnum tracking-tight" x-text="jam">--:--</div>
        <div class="text-sm text-neutral-500">{{ now()->translatedFormat('l, d F Y') }}</div>
        @if ($this->shiftHariIni)
            <div class="inline-flex items-center gap-2 mt-2 px-3 py-1 rounded-full text-xs font-semibold"
                 style="background:var(--brand-50);color:var(--brand-700)">
                {{ $this->shiftHariIni->nama }} ·
                {{ \Illuminate\Support\Str::of($this->shiftHariIni->jam_mulai)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($this->shiftHariIni->jam_selesai)->substr(0, 5) }}
                · toleransi {{ $this->shiftHariIni->toleransi_telat }}m
            </div>
        @else
            <div class="mt-2 text-xs text-neutral-400">Tidak ada shift terjadwal hari ini (mode catat)</div>
        @endif
    </div>

    {{-- Viewfinder kamera (diisi absen.js) --}}
    <div class="viewfinder mb-3">
        <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
        <div class="face-guide"></div>
        <div class="absolute top-3 left-3 right-3 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold text-white backdrop-blur"
                  :style="wajahAda ? 'background:rgba(22,163,74,.9)' : 'background:rgba(220,38,38,.9)'"
                  x-text="wajahAda ? 'Wajah terdeteksi' : 'Wajah tak terdeteksi'"></span>
            <span class="px-2.5 py-1 rounded-full bg-black/40 text-white/90 text-[11px] font-semibold backdrop-blur">Kamera depan</span>
        </div>
    </div>

    {{-- Cek: wajah + radius --}}
    <div class="grid grid-cols-2 gap-2 mb-4">
        <div class="chk" :class="wajahAda ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-600'"
             x-text="wajahAda ? 'Wajah OK' : 'Wajah tidak ada'"></div>
        <div class="chk" :class="dalamRadius ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-600'"
             x-text="lokasiTeks"></div>
    </div>

    {{-- Peta Leaflet (diisi absen.js) --}}
    <div x-ref="peta" class="w-full h-40 rounded-lg overflow-hidden mb-4 bg-neutral-100"></div>

    {{-- Stepper masuk → pulang --}}
    <div class="flex items-center gap-2 mb-4 px-1">
        <div class="flex-1 flex items-center gap-2">
            <span class="w-7 h-7 rounded-full grid place-items-center text-[11px] font-bold {{ $this->aksi === 'masuk' ? 'bg-brand-600 text-white' : 'bg-success-500 text-white' }}">1</span>
            <span class="text-sm font-semibold text-neutral-700">Masuk</span>
        </div>
        <div class="flex-1 h-0.5 bg-neutral-200"></div>
        <div class="flex-1 flex items-center gap-2 justify-end">
            <span class="text-sm font-semibold {{ $this->aksi === 'pulang' ? 'text-neutral-700' : 'text-neutral-400' }}">Pulang</span>
            <span class="w-7 h-7 rounded-full grid place-items-center text-[11px] font-bold {{ $this->aksi === 'pulang' ? 'bg-brand-600 text-white' : 'bg-neutral-100 text-neutral-400' }}">2</span>
        </div>
    </div>

    {{-- Tombol absen (aksi diisi Task 2/4) --}}
    <button type="button"
            class="btn btn-primary w-full !py-4 text-base mb-2"
            :disabled="!bolehAbsen"
            @click="ambil()">
        {{ $this->aksi === 'masuk' ? 'Absen Masuk' : 'Absen Pulang' }}
    </button>

    @if (session('absen_ok'))
        <p class="text-xs text-success-700 text-center mb-2">{{ session('absen_ok') }}</p>
    @endif
    @error('foto') <p class="text-xs text-danger-600 text-center mb-2">{{ $message }}</p> @enderror
    @error('lat')  <p class="text-xs text-danger-600 text-center mb-2">{{ $message }}</p> @enderror
    @error('akurasi') <p class="text-xs text-danger-600 text-center mb-2">{{ $message }}</p> @enderror

    <p class="text-[11px] text-neutral-400 text-center leading-relaxed mb-6">
        Foto disimpan sebagai bukti (WebP). Deteksi wajah hanya memastikan <b>ada wajah</b> (bukan pengenalan). Lokasi dicek terhadap radius kantor.
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
