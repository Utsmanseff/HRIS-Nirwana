{{-- Tanpa x-init: Alpine sudah memanggil init() milik x-data sendiri. Menulis keduanya
     membuat init() jalan dua kali → L.map() dua kali di kontainer yang sama. --}}
<div class="max-w-3xl mx-auto"
     x-data="petaPengaturan()">
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight">Pengaturan Absensi</h1>
        <p class="text-neutral-500 text-sm mt-1">
            Titik & radius kantor untuk validasi lokasi, serta pengingat otomatis bagi karyawan
            yang belum absen.
        </p>
    </div>

    @if (session('ok'))
        <div class="mb-4 px-4 py-2.5 rounded-lg text-sm font-medium"
             style="background:var(--success-50);color:var(--success-700)">{{ session('ok') }}</div>
    @endif

    <div class="card card-pad space-y-4">
        {{-- Peta drag-marker --}}
        <div x-ref="peta" wire:ignore class="peta w-full h-64 rounded-lg overflow-hidden bg-neutral-100"></div>
        <p class="text-[11px] text-neutral-400">Seret pin atau klik peta untuk memindahkan titik kantor. Lingkaran = radius.</p>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="field-label">Latitude</label>
                <input type="number" step="any" wire:model="officeLat" class="input font-mono @error('officeLat') input-error @enderror">
                @error('officeLat') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Longitude</label>
                <input type="number" step="any" wire:model="officeLong" class="input font-mono @error('officeLong') input-error @enderror">
                @error('officeLong') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Radius (meter)</label>
                <input type="number" wire:model.live="radiusM" class="input tnum @error('radiusM') input-error @enderror">
                @error('radiusM') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Maks akurasi GPS (m)</label>
                <input type="number" wire:model="maxAkurasiM" class="input tnum @error('maxAkurasiM') input-error @enderror">
                @error('maxAkurasiM') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="button" wire:click="simpanLokasi" class="btn btn-primary">Simpan Lokasi</button>
    </div>

    <div class="card card-pad space-y-4 mt-5">
        <div>
            <h2 class="font-extrabold tracking-tight">Pengingat Absen</h2>
            <p class="text-neutral-500 text-sm mt-1">
                Dikirim hanya kepada yang terlewat — yang sudah absen tidak menerima apa pun.
                Satu pengingat per shift, tanpa pengulangan.
            </p>
        </div>

        <label class="flex items-center gap-2 text-sm text-neutral-700">
            <input type="checkbox" wire:model="pengingatAktif" class="w-4 h-4 accent-brand-500">
            <span>Aktifkan pengingat absen</span>
        </label>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="field-label">Jeda setelah toleransi telat (menit)</label>
                <input type="number" wire:model="jedaMasukMenit" class="input tnum @error('jedaMasukMenit') input-error @enderror">
                @error('jedaMasukMenit') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Jeda setelah jam pulang (menit)</label>
                <input type="number" wire:model="jedaPulangMenit" class="input tnum @error('jedaPulangMenit') input-error @enderror">
                @error('jedaPulangMenit') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Ambang sesi nyangkut (jam)</label>
                <input type="number" wire:model="ambangNyangkutJam" class="input tnum @error('ambangNyangkutJam') input-error @enderror">
                @error('ambangNyangkutJam') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                <p class="text-[11px] text-neutral-400 mt-1">Untuk karyawan tanpa jadwal shift.</p>
            </div>
        </div>

        <button type="button" wire:click="simpanPengingat" class="btn btn-primary">Simpan Pengingat</button>
    </div>
</div>
