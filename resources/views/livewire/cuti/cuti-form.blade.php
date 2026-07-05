<div class="max-w-2xl mx-auto p-4 sm:p-6">
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('cuti') }}" class="text-sm text-neutral-400">&larr;</a>
        <h1 class="text-xl font-bold">Ajukan Cuti</h1>
    </div>

    <form wire:submit="simpan" class="space-y-5">
        {{-- Jenis: chip grid --}}
        <div>
            <label class="field-label">Jenis Cuti</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($jenisOptions as $j)
                    @php $aktif = (string) $j->id === (string) $jenisCutiId; @endphp
                    <button type="button" wire:click="$set('jenisCutiId', '{{ $j->id }}')"
                        class="rounded-lg border p-3 text-center transition {{ $aktif ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' : 'border-[var(--border)] bg-[var(--bg-surface)] hover:border-brand-300' }}">
                        <span class="grid place-items-center mb-1 {{ $aktif ? 'text-brand-600' : 'text-neutral-500' }}">
                            @switch($j->kode->value)
                                @case('cuti_tahunan')
                                    <svg width="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg>
                                    @break
                                @case('izin_biasa')
                                    <svg width="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1" stroke-linecap="round"/></svg>
                                    @break
                                @case('cuti_sakit')
                                    <svg width="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.5-7 10-7 10Z"/></svg>
                                    @break
                                @default
                                    <svg width="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="7" r="3"/><path d="M7 21v-4a5 5 0 0 1 10 0v4"/></svg>
                            @endswitch
                        </span>
                        <div class="text-[12px] font-bold">{{ $j->nama }}</div>
                        <div class="text-[10px] text-neutral-400">{{ $j->kode->subjudul() }}</div>
                    </button>
                @endforeach
            </div>
            @error('jenisCutiId') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror

            @if ($jenisTerpilih)
                <div class="flex gap-2.5 p-3 rounded-lg bg-brand-50 border border-brand-100 mt-2 text-xs text-brand-700">
                    <svg width="15" class="shrink-0 mt-px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1" stroke-linecap="round"/></svg>
                    <span>{!! $jenisTerpilih->kode->keterangan() !!}</span>
                </div>
            @endif
        </div>

        {{-- Tanggal --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="field-label">Tanggal Mulai</label>
                <input type="date" wire:model="tanggalMulai" class="input">
                @error('tanggalMulai') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label">Tanggal Akhir</label>
                <input type="date" wire:model="tanggalSelesai" class="input">
                @error('tanggalSelesai') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Jumlah hari --}}
        <div>
            <label class="field-label">Jumlah Hari <span class="text-neutral-400 font-normal">(isi manual)</span></label>
            <input type="number" min="1" max="6" wire:model="jumlahHari" class="input tnum">
            <div class="flex gap-2.5 p-2.5 rounded-lg bg-neutral-50 border border-neutral-200 mt-2 text-[11px] text-neutral-500">
                <svg width="14" class="shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1" stroke-linecap="round"/></svg>
                <span>≤ hari kalender dalam rentang, cuti tahunan ≤ saldo & maks 6/pengajuan, hari penuh. Hari kerja shift/akhir pekan dihitung sendiri. HRD verifikasi saat acc.</span>
            </div>
            @error('jumlahHari') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- Alasan --}}
        <div>
            <label class="field-label">Alasan</label>
            <textarea wire:model="alasan" rows="2" class="textarea" placeholder="mis. Acara keluarga"></textarea>
            @error('alasan') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- Lampiran (kondisional) --}}
        @if ($jenisTerpilih && $jenisTerpilih->butuh_lampiran)
            <div>
                <label class="field-label">Lampiran <span class="text-danger-500">*</span> <span class="text-neutral-400 font-normal">(jpg/png/pdf, maks 5MB)</span></label>
                <input type="file" wire:model="lampiran" accept=".jpg,.jpeg,.png,.webp,.pdf" class="input">
                <div class="text-[11px] text-neutral-400 mt-1">{{ $jenisTerpilih->kode->labelLampiran() ?? 'Unggah dokumen' }}. Wajib disertakan saat mengajukan.</div>
                <div wire:loading wire:target="lampiran" class="text-[11px] text-brand-600 mt-1">Mengunggah…</div>
                @error('lampiran') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>
        @endif

        {{-- Preview alur persetujuan --}}
        @if ($rantai->isNotEmpty())
            <div class="card !bg-transparent border-dashed">
                <div class="card-pad !py-3">
                    <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider mb-2.5">Alur Persetujuan</div>
                    <div class="flex items-center gap-1.5 text-[11px] font-semibold flex-wrap">
                        <span class="text-neutral-500">Kamu</span>
                        @foreach ($rantai as $s)
                            <svg width="14" class="text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            <span class="{{ $loop->last ? 'text-brand-600' : 'text-neutral-600' }}">{{ ucfirst($s['peran']->value) }}</span>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-neutral-400 mt-2">Berjenjang berurutan. Ditolak di tahap mana pun = batal, ajukan ulang.</p>
                </div>
            </div>
        @endif

        <div class="flex gap-2.5">
            <a href="{{ route('cuti') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary flex-1" wire:loading.attr="disabled">Ajukan</button>
        </div>
    </form>
</div>
