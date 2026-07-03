<div class="space-y-4 rise max-w-3xl mx-auto">
    <div>
        <h1 class="text-lg font-extrabold tracking-tight">{{ $karyawan ? 'Ubah Karyawan' : 'Tambah Karyawan' }}</h1>
        <p class="text-sm text-neutral-500">{{ $karyawan ? 'Perbarui data induk karyawan.' : 'Isi data induk karyawan baru beserta kontrak tahap awalnya.' }}</p>
    </div>

    {{-- Identitas --}}
    <div class="card card-pad space-y-3">
        <div class="card-title">Identitas</div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="field-label">NIP *</label>
                <input wire:model="nip" class="input @error('nip') input-error @enderror" placeholder="2026.07.0362">
                @error('nip') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Nama Lengkap *</label>
                <input wire:model="namaLengkap" class="input @error('namaLengkap') input-error @enderror" placeholder="mis. Andi Pratama">
                @error('namaLengkap') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Data Pribadi --}}
    <div class="card card-pad space-y-3">
        <div class="card-title">Data Pribadi</div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div><label class="field-label">NIK (KTP)</label><input wire:model="nik" class="input" placeholder="16 digit"></div>
            <div><label class="field-label">Tempat Lahir</label><input wire:model="tempatLahir" class="input" placeholder="Kota"></div>
            <div><label class="field-label">Tanggal Lahir</label><input type="date" wire:model="tanggalLahir" class="input"></div>
            <div>
                <label class="field-label">Jenis Kelamin</label>
                <select wire:model="jenisKelamin" class="input">
                    <option value="">— Pilih —</option><option value="L">Laki-laki</option><option value="P">Perempuan</option>
                </select>
            </div>
            <div><label class="field-label">Agama</label><input wire:model="agama" class="input"></div>
            <div>
                <label class="field-label">Status Nikah</label>
                <select wire:model="statusNikah" class="input">
                    <option value="">— Pilih —</option><option value="belum">Belum</option><option value="menikah">Menikah</option><option value="cerai">Cerai</option>
                </select>
            </div>
            <div class="sm:col-span-2"><label class="field-label">Pendidikan Terakhir</label><input wire:model="pendidikan" class="input" placeholder="mis. S1 Keperawatan"></div>
        </div>
        <div class="grid sm:grid-cols-3 gap-3 pt-1 border-t border-neutral-100">
            <div>
                <label class="field-label">Nomor SIP (nakes)</label>
                <input wire:model="sipNomor" class="input" placeholder="kosongkan bila bukan nakes">
            </div>
            <div>
                <label class="field-label">SIP Berlaku Mulai</label>
                <input type="date" wire:model="sipMulai" class="input @error('sipMulai') input-error @enderror">
                @error('sipMulai') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">SIP Berlaku Akhir</label>
                <input type="date" wire:model="sipAkhir" class="input @error('sipAkhir') input-error @enderror">
                @error('sipAkhir') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Kontak --}}
    <div class="card card-pad space-y-3">
        <div class="card-title">Kontak</div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div><label class="field-label">No. HP</label><input wire:model="noHp" class="input" placeholder="08xx xxxx xxxx"></div>
            <div>
                <label class="field-label">Email Pribadi</label>
                <input wire:model="email" class="input @error('email') input-error @enderror" placeholder="nama@email.com">
                @error('email') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2"><label class="field-label">Alamat</label><textarea wire:model="alamat" class="input" rows="2" placeholder="Alamat domisili"></textarea></div>
        </div>
    </div>

    {{-- Penempatan & Jabatan --}}
    <div class="card card-pad space-y-3">
        <div class="card-title">Penempatan &amp; Jabatan</div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="field-label">Unit Organisasi *</label>
                <select wire:model="orgUnitId" class="input @error('orgUnitId') input-error @enderror">
                    <option value="">— Pilih unit —</option>
                    @foreach ($unitOptions as $u)<option value="{{ $u->id }}">{{ $u->nama }}</option>@endforeach
                </select>
                @error('orgUnitId') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Jabatan *</label>
                <select wire:model="jabatanId" class="input @error('jabatanId') input-error @enderror">
                    <option value="">— Pilih jabatan —</option>
                    @foreach ($jabatanOptions as $j)<option value="{{ $j->id }}">{{ $j->nama }} (L{{ $j->level->value }})</option>@endforeach
                </select>
                @error('jabatanId') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Tanggal Masuk *</label>
                <input type="date" wire:model="tanggalMasuk" class="input @error('tanggalMasuk') input-error @enderror">
                @error('tanggalMasuk') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Kontrak tahap awal (hanya saat tambah — riwayat kontrak dikelola di detail) --}}
    @if (! $karyawan)
    <div class="card card-pad space-y-3">
        <div class="card-title">Kontrak / Tahap Awal</div>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="field-label">Jenis *</label>
                <select wire:model.live="jenisKontrak" class="input">
                    @foreach ($kontrakOptions as $jk)<option value="{{ $jk->value }}">{{ $jk->label() }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Tanggal Mulai *</label>
                <input type="date" wire:model="kontrakMulai" class="input @error('kontrakMulai') input-error @enderror">
                @error('kontrakMulai') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Tanggal Akhir {{ $jenisKontrak === 'tetap' ? '' : '*' }}</label>
                <input type="date" wire:model="kontrakAkhir" class="input @error('kontrakAkhir') input-error @enderror" @disabled($jenisKontrak === 'tetap')>
                @error('kontrakAkhir') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-3"><label class="field-label">Keterangan</label><input wire:model="kontrakKeterangan" class="input" placeholder="mis. Percobaan unpaid 2 minggu"></div>
        </div>
    </div>
    @endif

    <div class="flex gap-2">
        <button wire:click="simpan" class="btn btn-primary flex-1">{{ $karyawan ? 'Simpan Perubahan' : 'Simpan Karyawan' }}</button>
        <a href="{{ $karyawan ? route('sdm.karyawan.detail', $karyawan) : route('sdm.karyawan') }}" class="btn btn-ghost">Batal</a>
    </div>
</div>
