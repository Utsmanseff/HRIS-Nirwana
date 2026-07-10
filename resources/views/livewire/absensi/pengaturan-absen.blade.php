<div class="max-w-3xl mx-auto"
     x-data="petaPengaturan()"
     x-init="init({ lat: @entangle('officeLat'), long: @entangle('officeLong'), radius: @entangle('radiusM') })">
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight">Pengaturan Lokasi Absen</h1>
        <p class="text-neutral-500 text-sm mt-1">
            Titik & radius kantor untuk validasi lokasi saat absen. Absen di luar radius / akurasi GPS buruk ditolak.
        </p>
    </div>

    @if (session('ok'))
        <div class="mb-4 px-4 py-2.5 rounded-lg text-sm font-medium"
             style="background:var(--success-50);color:var(--success-700)">{{ session('ok') }}</div>
    @endif

    <div class="card card-pad space-y-4">
        {{-- Peta drag-marker --}}
        <div x-ref="peta" class="w-full h-64 rounded-lg overflow-hidden bg-neutral-100"></div>
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

        <button type="button" wire:click="simpan" class="btn btn-primary">Simpan Lokasi</button>
    </div>
</div>
