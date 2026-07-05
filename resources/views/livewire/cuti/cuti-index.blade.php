@php
    $badgeStatus = [
        'diajukan' => 'badge-warning',
        'diproses' => 'badge-warning',
        'disetujui' => 'badge-success',
        'ditolak' => 'badge-danger',
        'dibatalkan' => 'badge',
    ];
@endphp
<div class="max-w-3xl mx-auto p-4 sm:p-6 space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Cuti Saya</h1>
            <p class="text-sm text-neutral-400">Ajukan dan pantau pengajuan cuti Anda.</p>
        </div>
    </div>

    {{-- Panel jatah cuti tahunan --}}
    @if ($saldo->eligible())
        @php
            $jatah = $saldo->jatah();
            $terpakai = $saldo->terpakai();
            $pending = $saldo->pending();
            $sisa = $saldo->efektif();
            $persen = $jatah > 0 ? min(100, round(($terpakai + $pending) / $jatah * 100)) : 0;
        @endphp
        <div class="rounded-xl p-4 sm:p-5 text-white rise" style="background:var(--panel-glow),var(--panel-grad)">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-white/60 text-xs font-semibold uppercase tracking-wide">Sisa Cuti Tahunan</div>
                    <div class="text-4xl font-extrabold tnum mt-1">{{ $sisa }}<span class="text-lg text-white/50 font-bold"> hari</span></div>
                </div>
                <span class="badge text-[10px]" style="background:rgba(255,255,255,.15);color:#fff;border:0">{{ $saldo->periodeMulai()->format('Y') }}</span>
            </div>
            <div class="h-2 rounded-full bg-white/15 mt-3 overflow-hidden">
                <div class="h-full rounded-full bg-brand-400" style="width:{{ $persen }}%"></div>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                <div class="rounded-lg bg-white/10 py-2"><div class="text-[10px] text-white/55 font-semibold">JATAH</div><div class="font-bold tnum">{{ $jatah }}</div></div>
                <div class="rounded-lg bg-white/10 py-2"><div class="text-[10px] text-white/55 font-semibold">TERPAKAI</div><div class="font-bold tnum">{{ $terpakai }}</div></div>
                <div class="rounded-lg bg-white/10 py-2"><div class="text-[10px] text-white/55 font-semibold">PENDING</div><div class="font-bold tnum" style="color:#fcd34d">{{ $pending }}</div></div>
            </div>
            <div class="text-[11px] text-white/45 mt-2.5">
                Periode {{ $saldo->periodeMulai()->format('d M Y') }} – {{ $saldo->periodeSelesai()->subDay()->format('d M Y') }} · sisa hangus tiap periode · maks 6 hari/pengajuan
            </div>
        </div>
    @else
        <div class="card card-pad rise">
            <div class="text-sm font-semibold mb-1">Jatah Cuti Tahunan</div>
            <p class="text-sm text-neutral-400">Belum berhak cuti tahunan (masa kerja belum genap 1 tahun sejak kontrak pertama). Anda masih dapat mengajukan izin, sakit, atau melahirkan.</p>
        </div>
    @endif

    <a href="{{ route('cuti.ajukan') }}" class="btn btn-primary w-full !py-3">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>Ajukan Cuti
    </a>

    {{-- Info hari libur mendatang --}}
    @if ($hariLibur->isNotEmpty())
        <div class="card card-pad">
            <div class="text-sm font-semibold mb-2">Hari Libur / Cuti Bersama Mendatang</div>
            <ul class="text-sm space-y-1">
                @foreach ($hariLibur as $hl)
                    <li class="flex justify-between"><span>{{ $hl->nama }}</span><span class="text-neutral-400 tnum">{{ $hl->tanggal->format('d M Y') }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Riwayat pengajuan --}}
    <div>
        <div class="text-sm font-semibold mb-2.5">Riwayat Pengajuan</div>
        <div class="space-y-2.5">
            @forelse ($pengajuan as $p)
                @php $pend = in_array($p->status->value, ['diajukan', 'diproses'], true); @endphp
                <a href="{{ route('cuti.detail', $p) }}" class="card card-pad !p-3.5 block {{ $p->status->value === 'ditolak' ? 'opacity-90' : '' }}">
                    <div class="flex items-start justify-between gap-3 {{ $pend ? 'mb-2' : '' }}">
                        <div>
                            <span class="badge badge-brand">{{ $p->jenisCuti->nama }}</span>
                            <div class="text-sm font-bold mt-1.5 tnum">{{ $p->tanggal_mulai->format('d M Y') }}@if($p->tanggal_selesai->ne($p->tanggal_mulai)) – {{ $p->tanggal_selesai->format('d M Y') }}@endif</div>
                            <div class="text-xs text-neutral-400">{{ $p->jumlah_hari }} hari @if($p->alasan)· {{ \Illuminate\Support\Str::limit($p->alasan, 40) }}@endif</div>
                        </div>
                        <span class="badge {{ $badgeStatus[$p->status->value] ?? 'badge' }} shrink-0"><span class="dot"></span>{{ ucfirst($p->status->value) }}</span>
                    </div>
                    @if ($pend && $p->approval->isNotEmpty())
                        <div class="flex items-center gap-1.5 pt-2 border-t border-neutral-100">
                            @foreach ($p->approval as $a)
                                @php
                                    $st = $a->status->value;
                                    $warna = $st === 'setuju' ? 'text-success-600' : ($st === 'tolak' ? 'text-danger-600' : ($loop->first || $p->approval[$loop->index - 1]->status->value === 'setuju' ? 'text-warning-600' : 'text-neutral-400'));
                                    $bg = $st === 'setuju' ? 'bg-success-500 text-white' : ($st === 'tolak' ? 'bg-danger-500 text-white' : 'bg-neutral-200');
                                @endphp
                                @if (! $loop->first)<div class="flex-1 h-px bg-neutral-200"></div>@endif
                                <div class="flex items-center gap-1 text-[11px] font-semibold {{ $warna }}">
                                    <span class="w-4 h-4 rounded-full grid place-items-center text-[8px] {{ $bg }}">{{ $st === 'setuju' ? '✓' : ($st === 'tolak' ? '✕' : '●') }}</span>{{ ucfirst($a->peran->value) }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </a>
            @empty
                <div class="card card-pad"><p class="text-sm text-neutral-400">Belum ada pengajuan.</p></div>
            @endforelse
        </div>
    </div>
</div>
