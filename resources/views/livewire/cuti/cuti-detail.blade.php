@php
    $badgeStatus = [
        'diajukan' => 'badge-warning', 'diproses' => 'badge-warning',
        'disetujui' => 'badge-success', 'ditolak' => 'badge-danger', 'dibatalkan' => 'badge',
    ];
    $pot = $pengajuan->jenisCuti->potong_saldo;
    $pend = in_array($pengajuan->status->value, ['diajukan', 'diproses'], true);
    // Tahap "berjalan" = approval menunggu pertama yang semua sebelumnya sudah setuju.
    $idxAktif = null;
    foreach ($pengajuan->approval as $i => $a) {
        if ($a->status->value === 'menunggu') { $idxAktif = $i; break; }
    }
@endphp
<div class="max-w-2xl mx-auto p-4 sm:p-6 space-y-4">
    <style>.tl2::before{content:'';position:absolute;left:13px;top:6px;bottom:6px;width:2px;background:var(--border);}</style>

    <a href="{{ route('cuti') }}" class="text-sm text-neutral-400">&larr; Kembali</a>

    {{-- Ringkasan --}}
    <div class="card card-pad rise">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <span class="badge badge-brand">{{ $pengajuan->jenisCuti->nama }}</span>
                <div class="text-lg font-extrabold mt-2 tnum">{{ $pengajuan->tanggal_mulai->format('d M Y') }}@if($pengajuan->tanggal_selesai->ne($pengajuan->tanggal_mulai)) – {{ $pengajuan->tanggal_selesai->format('d M Y') }}@endif</div>
            </div>
            <span class="badge {{ $badgeStatus[$pengajuan->status->value] ?? 'badge' }} shrink-0"><span class="dot"></span>{{ ucfirst($pengajuan->status->value) }}</span>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><div class="text-xs text-neutral-400">Jumlah hari</div><div class="font-bold tnum">{{ $pengajuan->jumlah_hari }} hari</div></div>
            <div>
                <div class="text-xs text-neutral-400">Potong jatah</div>
                <div class="font-bold">
                    @if ($pot)
                        {{ $pengajuan->jumlah_hari }} hari
                        @if ($pend)<span class="text-xs text-warning-600 font-semibold">(ditahan)</span>
                        @elseif ($pengajuan->status->value === 'disetujui')<span class="text-xs text-success-600 font-semibold">(dipotong)</span>@endif
                    @else
                        <span class="text-neutral-400">—</span>
                    @endif
                </div>
            </div>
            @if ($pengajuan->alasan)
                <div class="col-span-2"><div class="text-xs text-neutral-400">Alasan</div><div class="font-medium">{{ $pengajuan->alasan }}</div></div>
            @endif
            <div class="col-span-2"><div class="text-xs text-neutral-400">Diajukan</div><div class="font-medium">{{ $pengajuan->created_at->format('d M Y · H:i') }}</div></div>
            @if ($pengajuan->lampiran_path)
                <div class="col-span-2"><a href="{{ route('cuti.lampiran', $pengajuan) }}" target="_blank" class="btn btn-secondary btn-sm">Lihat Lampiran</a></div>
            @endif
        </div>
    </div>

    {{-- Timeline persetujuan --}}
    <div class="card card-pad">
        <div class="text-[13px] font-bold mb-4">Status Persetujuan</div>
        <div class="relative tl2 pl-10 space-y-5">
            <div class="relative">
                <span class="absolute -left-10 top-0 w-7 h-7 rounded-full bg-success-500 ring-4 grid place-items-center" style="--tw-ring-color:var(--bg-surface)"><svg width="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <div class="text-sm font-semibold">Pengajuan dibuat</div>
                <div class="text-xs text-neutral-400">{{ $pengajuan->karyawan->nama_lengkap }} · {{ $pengajuan->created_at->format('d M, H:i') }}</div>
            </div>
            @foreach ($pengajuan->approval as $i => $a)
                @php
                    $st = $a->status->value;
                    $aktif = $i === $idxAktif && $pend;
                    if ($st === 'setuju') { $lingkar = 'bg-success-500'; $isi = '<svg width="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/></svg>'; $judul = 'text-neutral-800'; }
                    elseif ($st === 'tolak') { $lingkar = 'bg-danger-500'; $isi = '<svg width="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>'; $judul = 'text-danger-700'; }
                    elseif ($aktif) { $lingkar = 'bg-warning-500 animate-pulse'; $isi = '<span class="w-2 h-2 rounded-full bg-white"></span>'; $judul = 'text-warning-700'; }
                    else { $lingkar = 'bg-neutral-200'; $isi = ''; $judul = 'text-neutral-400'; }
                    $label = $st === 'setuju' ? 'Disetujui '.ucfirst($a->peran->value) : ($st === 'tolak' ? 'Ditolak '.ucfirst($a->peran->value) : ($aktif ? 'Menunggu '.ucfirst($a->peran->value) : ucfirst($a->peran->value).($a->peran->value === 'hrd' ? ' (final)' : '')));
                @endphp
                <div class="relative">
                    <span class="absolute -left-10 top-0 w-7 h-7 rounded-full {{ $lingkar }} ring-4 grid place-items-center" style="--tw-ring-color:var(--bg-surface)">{!! $isi !!}</span>
                    <div class="text-sm font-semibold {{ $judul }}">{{ $label }}</div>
                    <div class="text-xs text-neutral-400">
                        {{ $a->approver->nama_lengkap }}
                        @if (in_array($st, ['setuju', 'tolak'], true) && $a->updated_at)· {{ $a->updated_at->format('d M, H:i') }}@elseif ($aktif)· belum ditinjau @else· menunggu tahap sebelumnya @endif
                    </div>
                    @if ($a->catatan)
                        <div class="text-xs text-neutral-500 mt-1 p-2 rounded-md bg-neutral-50 border border-neutral-100">"{{ $a->catatan }}"</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if ($pot && $pend)
        <div class="flex gap-2.5 p-3 rounded-lg bg-info-50 border border-info-100 text-xs text-info-700">
            <svg width="16" class="shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1" stroke-linecap="round"/></svg>
            <span class="leading-relaxed">Jatah dipotong saat <b>disetujui HRD</b>. Sekarang {{ $pengajuan->jumlah_hari }} hari <b>ditahan</b> (pending).</span>
        </div>
    @endif

    @if ($pend)
        <button x-on:click="$store.konfirmasi.buka({ judul: 'Batalkan pengajuan ini?', pesan: 'Pengajuan cuti akan dibatalkan dan tidak diproses lebih lanjut.', varian: 'primary', labelYa: 'Batalkan', onConfirm: () => $wire.batalkan() })" class="btn btn-secondary w-full text-danger-600 !border-danger-100">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>Batalkan Pengajuan
        </button>
    @endif
</div>
