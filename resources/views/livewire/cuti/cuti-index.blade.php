<div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Cuti Saya</h1>
            <p class="text-sm text-muted">Ajukan dan pantau pengajuan cuti Anda.</p>
        </div>
        <a href="{{ Route::has('cuti.ajukan') ? route('cuti.ajukan') : '#' }}" class="btn btn-primary">Ajukan Cuti</a>
    </div>

    {{-- Widget saldo cuti tahunan --}}
    <div class="card p-4">
        <div class="text-sm font-semibold mb-3">Saldo Cuti Tahunan</div>
        @if ($saldo->eligible())
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div><div class="text-2xl font-bold">{{ $saldo->jatah() }}</div><div class="text-xs text-muted">Jatah</div></div>
                <div><div class="text-2xl font-bold">{{ $saldo->terpakai() }}</div><div class="text-xs text-muted">Terpakai</div></div>
                <div><div class="text-2xl font-bold">{{ $saldo->pending() }}</div><div class="text-xs text-muted">Menunggu</div></div>
                <div><div class="text-2xl font-bold text-brand-700">{{ $saldo->efektif() }}</div><div class="text-xs text-muted">Sisa</div></div>
            </div>
            <div class="text-xs text-muted mt-3">Periode {{ $saldo->periodeMulai()->format('d M Y') }} – {{ $saldo->periodeSelesai()->subDay()->format('d M Y') }}</div>
        @else
            <p class="text-sm text-muted">Belum berhak cuti tahunan (belum genap 1 tahun sejak kontrak PKWT pertama). Anda masih dapat mengajukan izin biasa.</p>
        @endif
    </div>

    {{-- Info hari libur mendatang --}}
    @if ($hariLibur->isNotEmpty())
        <div class="card p-4">
            <div class="text-sm font-semibold mb-2">Hari Libur / Cuti Bersama Mendatang</div>
            <ul class="text-sm space-y-1">
                @foreach ($hariLibur as $hl)
                    <li class="flex justify-between"><span>{{ $hl->nama }}</span><span class="text-muted">{{ $hl->tanggal->format('d M Y') }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Riwayat pengajuan sendiri --}}
    <div class="card p-4">
        <div class="text-sm font-semibold mb-3">Riwayat Pengajuan</div>
        @forelse ($pengajuan as $p)
            <a href="{{ Route::has('cuti.detail') ? route('cuti.detail', $p) : '#' }}" class="flex items-center justify-between py-2 border-b border-[var(--border)] last:border-0 hover:bg-[var(--bg-muted)] px-2 rounded">
                <div>
                    <div class="text-sm font-medium">{{ $p->jenisCuti->nama }}</div>
                    <div class="text-xs text-muted">{{ $p->tanggal_mulai->format('d M Y') }} – {{ $p->tanggal_selesai->format('d M Y') }} · {{ $p->jumlah_hari }} hari</div>
                </div>
                <span class="badge">{{ ucfirst($p->status->value) }}</span>
            </a>
        @empty
            <p class="text-sm text-muted">Belum ada pengajuan.</p>
        @endforelse
    </div>
</div>
