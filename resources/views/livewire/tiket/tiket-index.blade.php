<div class="space-y-6 rise">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">{{ $adalahTim ? 'Antrian Tiket' : 'Tiket Saya' }}</h1>
            <p class="text-neutral-500 text-sm mt-1">
                {{ $adalahTim ? 'Antrean bersama tim — semua anggota tim bisa mengerjakan (tanpa assign PIC).' : 'Tiket yang Anda laporkan.' }}
            </p>
        </div>
        @if (\Illuminate\Support\Facades\Route::has('tiket.buat'))
            <a href="{{ route('tiket.buat') }}" class="btn btn-primary">+ {{ $adalahTim ? 'Catat Tiket' : 'Buat Tiket' }}</a>
        @endif
    </div>

    <div class="card">
        {{-- Toolbar --}}
        <div class="p-4 flex flex-wrap items-center gap-2.5 border-b border-neutral-100">
            <div class="relative flex-1 min-w-[200px]">
                <input class="input" wire:model.live.debounce.300ms="q" placeholder="Cari nomor / judul…">
            </div>
            <select class="select w-auto" wire:model.live="status">
                <option value="">Semua Status</option>
                @foreach ($statusOpsi as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
            </select>
            @if ($adalahTim)
                <select class="select w-auto" wire:model.live="prioritas">
                    <option value="">Semua Prioritas</option>
                    @foreach ($prioritasOpsi as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach
                </select>
                <select class="select w-auto" wire:model.live="jenis">
                    <option value="">Semua Jenis</option>
                    @foreach ($jenisOpsi as $j)<option value="{{ $j->value }}">{{ $j->label() }}</option>@endforeach
                </select>
            @endif
        </div>

        @if ($tiket->isEmpty())
            <p class="card-pad text-sm text-neutral-400">Belum ada tiket. 🎉</p>
        @else
            <div class="divide-y divide-neutral-100">
                @foreach ($tiket as $t)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('tiket.detail') ? route('tiket.detail', $t) : '#' }}"
                       class="flex items-start justify-between gap-3 p-4 hover:bg-neutral-50 transition">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-xs text-neutral-400">{{ $t->nomor }}</span>
                                <span class="badge badge-neutral text-[10px]">Tim {{ $t->tim->label() }}</span>
                                <span class="badge {{ $t->prioritas->badge() }} text-[10px]">{{ $t->prioritas->label() }}</span>
                            </div>
                            <div class="text-sm font-semibold mt-1 truncate">{{ $t->judul }}</div>
                            <div class="text-xs text-neutral-400 mt-0.5">
                                {{ $t->jenis->label() }}
                                @if ($t->pelapor) · {{ $t->pelapor->nama_lengkap }}@elseif ($adalahTim) · internal @endif
                                · {{ $t->waktu_lapor->translatedFormat('j M H:i') }}
                            </div>
                        </div>
                        <span class="badge {{ $t->status->badge() }} shrink-0"><span class="dot"></span>{{ $t->status->label() }}</span>
                    </a>
                @endforeach
            </div>
            <div class="p-3 border-t border-neutral-100">{{ $tiket->links('livewire.sdm.partials.pager') }}</div>
        @endif
    </div>
</div>
