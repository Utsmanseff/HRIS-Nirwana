<div class="max-w-3xl mx-auto p-4 sm:p-6">
    <h1 class="text-xl font-bold mb-4">Ajukan Cuti</h1>

    <form wire:submit="simpan" class="card p-5 space-y-4">
        <div>
            <label class="label">Jenis Cuti</label>
            <select wire:model.live="jenisCutiId" class="input">
                <option value="">— pilih —</option>
                @foreach ($jenisOptions as $j)
                    <option value="{{ $j->id }}">{{ $j->nama }}</option>
                @endforeach
            </select>
            @error('jenisCutiId') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="label">Tanggal Mulai</label>
                <input type="date" wire:model="tanggalMulai" class="input">
                @error('tanggalMulai') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="label">Tanggal Selesai</label>
                <input type="date" wire:model="tanggalSelesai" class="input">
                @error('tanggalSelesai') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="label">Jumlah Hari</label>
                <input type="number" min="1" wire:model="jumlahHari" class="input">
                @error('jumlahHari') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label class="label">Alasan / Keterangan</label>
            <textarea wire:model="alasan" rows="2" class="input"></textarea>
            @error('alasan') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label">Lampiran (jpg/png/pdf, maks 5MB)</label>
            <input type="file" wire:model="lampiran" accept=".jpg,.jpeg,.png,.webp,.pdf" class="input">
            @error('lampiran') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex gap-2 justify-end">
            <a href="{{ route('cuti') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
        </div>
    </form>
</div>
