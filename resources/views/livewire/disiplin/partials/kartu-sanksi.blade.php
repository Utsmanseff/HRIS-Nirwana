<div class="card card-pad flex flex-wrap items-center justify-between gap-3">
    <div>
        <div class="font-semibold">{{ $s->tingkat->label() }}
            <span class="badge badge-neutral ml-1">{{ $s->status->label() }}</span>
        </div>
        <div class="text-xs text-neutral-400">
            @if ($s->nomor_surat) <span class="font-mono">{{ $s->nomor_surat }}</span> · @endif
            @if ($s->berlaku_sampai) Berlaku s.d. {{ $s->berlaku_sampai->translatedFormat('d M Y') }} @endif
        </div>
        <div class="text-sm text-neutral-500 mt-0.5">{{ $s->uraian }}</div>
    </div>
    @if ($s->status === \App\Enums\StatusSanksi::Diterbitkan && $s->surat_path)
        <a href="{{ route('disiplin.surat', $s) }}" target="_blank" class="btn btn-secondary btn-sm">Lihat Surat</a>
    @endif
</div>
