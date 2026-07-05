<div class="max-w-2xl mx-auto p-4 sm:p-6 space-y-5">
    <a href="{{ route('cuti') }}" class="text-sm text-muted">&larr; Kembali</a>

    <div class="card p-5 space-y-3">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold">{{ $pengajuan->jenisCuti->nama }}</h1>
            <span class="badge">{{ ucfirst($pengajuan->status->value) }}</span>
        </div>
        <div class="text-sm text-muted">
            {{ $pengajuan->tanggal_mulai->format('d M Y') }} – {{ $pengajuan->tanggal_selesai->format('d M Y') }} · {{ $pengajuan->jumlah_hari }} hari
        </div>
        @if ($pengajuan->alasan)
            <p class="text-sm">{{ $pengajuan->alasan }}</p>
        @endif
        @if ($pengajuan->lampiran_path)
            <a href="{{ route('cuti.lampiran', $pengajuan) }}" target="_blank" class="btn btn-ghost btn-sm">Lihat Lampiran</a>
        @endif

        @if (in_array($pengajuan->status->value, ['diajukan', 'diproses']))
            <button wire:click="batalkan" wire:confirm="Batalkan pengajuan ini?" class="btn btn-danger btn-sm">Batalkan Pengajuan</button>
        @endif
    </div>

    <div class="card p-5">
        <div class="text-sm font-semibold mb-3">Progres Persetujuan</div>
        <ol class="space-y-2">
            @foreach ($pengajuan->approval as $a)
                <li class="flex items-center justify-between text-sm">
                    <span>{{ $loop->iteration }}. {{ $a->approver->nama_lengkap }} <span class="text-muted">({{ ucfirst($a->peran->value) }})</span></span>
                    <span class="badge">{{ ucfirst($a->status->value) }}</span>
                </li>
            @endforeach
        </ol>
    </div>
</div>
