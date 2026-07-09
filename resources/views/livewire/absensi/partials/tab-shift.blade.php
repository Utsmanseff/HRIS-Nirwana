<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div class="text-sm font-semibold">Shift <span class="text-brand-600">{{ $unit->nama }}</span></div>
    </div>

    <div class="flex gap-2.5 p-3 rounded-lg bg-info-50 border border-info-100 text-xs text-info-700">
        Shift dimiliki per-unit. Nama boleh sama antar unit tapi jamnya beda. Kode singkat (P/SI/M) diketik di grid jadwal. Karyawan tanpa jadwal = tidak dievaluasi telat.
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($daftarShift as $s)
            <div class="rounded-lg border border-neutral-200 p-4 @if(! $s->aktif) opacity-50 @endif">
                <div class="flex items-center justify-between mb-2">
                    <span class="badge" style="background:{{ $s->warna }}22;color:{{ $s->warna }};border-color:{{ $s->warna }}55">{{ $s->kode }} · {{ $s->nama }}</span>
                    <div class="flex gap-1">
                        <button class="btn btn-ghost btn-icon btn-sm" wire:click="editShift({{ $s->id }})" title="Ubah">✎</button>
                        <button class="btn btn-ghost btn-icon btn-sm" wire:click="toggleShiftAktif({{ $s->id }})" title="Aktif/Nonaktif">◐</button>
                    </div>
                </div>
                <div class="text-lg font-extrabold tnum">{{ \Illuminate\Support\Str::substr($s->jam_mulai,0,5) }} – {{ \Illuminate\Support\Str::substr($s->jam_selesai,0,5) }}@if($s->lintasHari())<span class="text-xs text-neutral-400"> · lintas hari</span>@endif</div>
                <div class="text-xs text-neutral-400 mt-1">Toleransi telat: {{ $s->toleransi_telat }} menit</div>
            </div>
        @endforeach
    </div>

    {{-- Form tambah/ubah shift --}}
    <div class="rounded-lg border border-neutral-200 p-4 max-w-xl">
        <div class="text-sm font-semibold mb-3">{{ $editShiftId ? 'Ubah Shift' : 'Tambah Shift' }}</div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="field-label">Nama</label><input class="input" wire:model="sNama" placeholder="Pagi">@error('sNama')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Kode</label><input class="input" wire:model="sKode" placeholder="P" maxlength="4">@error('sKode')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Warna</label><input type="color" class="input h-10 p-1" wire:model="sWarna"></div>
            <div><label class="field-label">Toleransi (menit)</label><input type="number" class="input tnum" wire:model="sToleransi">@error('sToleransi')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Jam mulai</label><input type="time" class="input" wire:model="sMulai">@error('sMulai')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Jam selesai</label><input type="time" class="input" wire:model="sSelesai">@error('sSelesai')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
        </div>
        <div class="flex gap-2 mt-3">
            <button class="btn btn-primary btn-sm" wire:click="simpanShift">Simpan</button>
            @if($editShiftId)<button class="btn btn-secondary btn-sm" wire:click="batalShift">Batal</button>@endif
        </div>
    </div>
</div>
