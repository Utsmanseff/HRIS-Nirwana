<div class="space-y-6 rise">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight">Kelola Sanksi</h1>
            <p class="text-sm text-neutral-500">Buat sanksi langsung, cabut, dan pantau semua sanksi.</p>
        </div>
    </div>

    @if (session('disiplin_ok'))
        <div class="card card-pad text-sm" style="border-color:var(--brand-200);color:var(--brand-700)">{{ session('disiplin_ok') }}</div>
    @endif

    {{-- Filter --}}
    <div class="card card-pad grid gap-3 sm:grid-cols-3">
        <input type="search" wire:model.live.debounce.300ms="cari" class="input" placeholder="Cari nama / NIP…">
        <select wire:model.live="filterStatus" class="input">
            <option value="">Semua status</option>
            @foreach ($statusOpsi as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterTingkat" class="input">
            <option value="">Semua tingkat</option>
            @foreach ($tingkatOpsi as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Daftar --}}
    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-neutral-400 border-b border-neutral-100">
                <tr>
                    <th class="px-4 py-3">Karyawan</th>
                    <th class="px-4 py-3">Tingkat</th>
                    <th class="px-4 py-3">Pengusul</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $s)
                    <tr class="border-b border-neutral-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $s->karyawan->nama_lengkap }}</div>
                            <div class="text-xs text-neutral-400 font-mono">NIP {{ $s->karyawan->nip }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $s->tingkat->label() }}</td>
                        <td class="px-4 py-3">{{ $s->pengusul->nama_lengkap }}</td>
                        <td class="px-4 py-3"><span class="badge badge-neutral">{{ $s->status->label() }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if ($s->status === \App\Enums\StatusSanksi::Diterbitkan && $s->surat_path)
                                <a href="{{ route('disiplin.surat', $s) }}" target="_blank" class="btn btn-secondary btn-sm">Surat</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-neutral-400">Belum ada sanksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
