<div class="space-y-5">
    <div class="flex gap-2.5 p-3 rounded-lg bg-info-50 border border-info-100 text-xs text-info-700 leading-relaxed">
        <svg width="15" class="shrink-0 mt-px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1" stroke-linecap="round"/></svg>
        <span>Shift dimiliki <b>per-unit</b>. Nama boleh sama antar unit tapi jamnya beda. Kode singkat (P/SI/M) diketik di grid jadwal. Karyawan tanpa jadwal = tidak dievaluasi telat.</span>
    </div>

    @if($daftarShift->isEmpty())
        <div class="text-center text-sm text-neutral-400 py-6">Belum ada shift. Tambahkan di bawah.</div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($daftarShift as $s)
                <div class="rounded-xl border border-neutral-200 p-4 @if(! $s->aktif) opacity-55 @endif">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="badge" style="background:{{ $s->warna }}1f;color:{{ $s->warna }};border-color:{{ $s->warna }}55">
                            <span class="w-4 h-4 -ml-0.5 rounded grid place-items-center text-[10px] font-extrabold" style="background:{{ $s->warna }};color:#fff">{{ $s->kode }}</span>
                            {{ $s->nama }}
                        </span>
                        <div class="flex gap-0.5">
                            <button class="btn btn-ghost btn-icon btn-sm" wire:click="editShift({{ $s->id }})" title="Ubah">
                                <svg width="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                            </button>
                            <button class="btn btn-ghost btn-icon btn-sm" wire:click="toggleShiftAktif({{ $s->id }})" title="{{ $s->aktif ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <svg width="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64A9 9 0 1 1 5.64 6.64M12 2v10" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="text-lg font-extrabold tnum">{{ \Illuminate\Support\Str::substr($s->jam_mulai,0,5) }} – {{ \Illuminate\Support\Str::substr($s->jam_selesai,0,5) }}</div>
                    <div class="text-xs text-neutral-400 mt-1">Toleransi telat: {{ $s->toleransi_telat }} menit @if($s->lintasHari())<span class="text-brand-600 font-semibold">· lintas hari</span>@endif</div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Form tambah/ubah shift --}}
    <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 max-w-2xl">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-6 h-6 rounded-lg bg-brand-100 text-brand-700 grid place-items-center">
                <svg width="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            </span>
            <div class="text-sm font-semibold">{{ $editShiftId ? 'Ubah Shift' : 'Tambah Shift' }}</div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div class="col-span-2 md:col-span-1"><label class="field-label">Nama</label><input class="input" wire:model="sNama" placeholder="Pagi">@error('sNama')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Kode</label><input class="input uppercase" wire:model="sKode" placeholder="P" maxlength="4">@error('sKode')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Warna</label><input type="color" class="input h-10 p-1 cursor-pointer" wire:model="sWarna"></div>
            <div><label class="field-label">Jam mulai</label><input type="time" class="input" wire:model="sMulai">@error('sMulai')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Jam selesai</label><input type="time" class="input" wire:model="sSelesai">@error('sSelesai')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="field-label">Toleransi (menit)</label><input type="number" class="input tnum" wire:model="sToleransi">@error('sToleransi')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror</div>
        </div>
        <div class="flex gap-2 mt-4">
            <button class="btn btn-primary btn-sm" wire:click="simpanShift">
                <svg width="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ $editShiftId ? 'Perbarui' : 'Simpan' }}
            </button>
            @if($editShiftId)<button class="btn btn-secondary btn-sm" wire:click="batalShift">Batal</button>@endif
        </div>
    </div>
</div>
