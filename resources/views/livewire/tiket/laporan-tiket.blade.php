<div class="space-y-6 rise">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Laporan Tiket</h1>
            <p class="text-neutral-500 text-sm mt-1">Metrik & daftar tiket tim Anda.</p>
        </div>
        <div class="flex gap-1.5">
            <a href="{{ route('tiket.laporan.daftar') }}?{{ http_build_query($query) }}&format=xlsx" class="btn btn-ghost btn-sm">Excel</a>
            <a href="{{ route('tiket.laporan.daftar') }}?{{ http_build_query($query) }}&format=pdf" class="btn btn-ghost btn-sm">PDF</a>
        </div>
    </div>

    {{-- Metrik per tim --}}
    @if (count($metrik))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($metrik as $m)
                <div class="card card-pad">
                    <div class="field-label">Tim {{ \App\Enums\TimTeknis::from($m['tim'])->label() }}</div>
                    <div class="text-xs text-neutral-400 mb-2">{{ $m['jumlah'] }} tiket</div>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="rounded-lg bg-neutral-50 py-2"><div class="text-[10px] text-neutral-400 font-semibold">RATA RESPON</div><div class="font-bold tnum">{{ $m['rata_respon'] !== null ? round($m['rata_respon']).' mnt' : '—' }}</div></div>
                        <div class="rounded-lg bg-neutral-50 py-2"><div class="text-[10px] text-neutral-400 font-semibold">RATA SELESAI</div><div class="font-bold tnum">{{ $m['rata_penyelesaian'] !== null ? round($m['rata_penyelesaian']).' mnt' : '—' }}</div></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card">
        {{-- Filter strip --}}
        <div class="p-4 flex flex-wrap items-center gap-2.5 border-b border-neutral-100">
            <input type="date" class="input w-auto" wire:model.live="dari">
            <span class="text-neutral-400 text-sm">s/d</span>
            <input type="date" class="input w-auto" wire:model.live="sampai">
            <select class="select w-auto" wire:model.live="status"><option value="">Semua Status</option>@foreach ($statusOpsi as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach</select>
            <select class="select w-auto" wire:model.live="prioritas"><option value="">Semua Prioritas</option>@foreach ($prioritasOpsi as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach</select>
            <select class="select w-auto" wire:model.live="jenis"><option value="">Semua Jenis</option>@foreach ($jenisOpsi as $j)<option value="{{ $j->value }}">{{ $j->label() }}</option>@endforeach</select>
        </div>

        <table class="table rtable">
            <thead><tr><th>Nomor</th><th>Judul</th><th>Tim</th><th>Pelapor</th><th>Prioritas</th><th>Status</th><th>Lapor</th></tr></thead>
            <tbody>
                @forelse ($tiket as $t)
                    <tr class="table-row-link" onclick="window.location='{{ route('tiket.detail', $t) }}'">
                        <td class="font-mono text-[13px] text-neutral-500">{{ $t->nomor }}</td>
                        <td data-primary><div class="font-semibold">{{ $t->judul }}</div><div class="text-xs text-neutral-400">{{ $t->jenis->label() }}</div></td>
                        <td>{{ $t->tim->label() }}</td>
                        <td class="text-[13px]">{{ $t->pelapor?->nama_lengkap ?? 'Internal' }}</td>
                        <td><span class="badge {{ $t->prioritas->badge() }}">{{ $t->prioritas->label() }}</span></td>
                        <td><span class="badge {{ $t->status->badge() }}"><span class="dot"></span>{{ $t->status->label() }}</span></td>
                        <td class="text-xs text-neutral-400 tnum">{{ $t->waktu_lapor->translatedFormat('j M H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-sm text-neutral-400 py-6">Tidak ada tiket.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3 border-t border-neutral-100">{{ $tiket->links('livewire.sdm.partials.pager') }}</div>
    </div>
</div>
