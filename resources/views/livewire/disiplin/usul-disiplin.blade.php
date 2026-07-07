<div class="max-w-3xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex items-center justify-between gap-2">
        <h1 class="text-xl font-bold">Usul Sanksi</h1>
    </div>

    @if (session('disiplin_ok'))
        <div class="rounded-lg bg-brand-50 border border-brand-100 text-brand-700 text-sm px-4 py-3">
            {{ session('disiplin_ok') }}
        </div>
    @endif

    {{-- Form usul --}}
    <section class="card rise">
        <div class="card-header"><div class="card-title">Karyawan</div></div>
        <div class="card-pad">
            @if ($karyawanTerpilih)
                <div class="flex items-center justify-between gap-3 rounded-lg border border-[var(--border)] bg-[var(--bg-surface)] p-3">
                    <div class="min-w-0">
                        <div class="text-sm font-bold truncate">{{ $karyawanTerpilih->nama_lengkap }}</div>
                        <div class="text-xs text-neutral-400 tnum">{{ $karyawanTerpilih->nip }}</div>
                    </div>
                    <button type="button" wire:click="batalKaryawan" class="text-xs font-semibold text-brand-600">Ganti</button>
                </div>
            @else
                <label class="field-label">Cari bawahan (nama / NIP)</label>
                <input type="text" wire:model.live.debounce.300ms="cari" class="input" placeholder="Ketik nama atau NIP…" autocomplete="off">
                @if ($hasilCari->isNotEmpty())
                    <div class="mt-2 rounded-lg border border-[var(--border)] divide-y divide-[var(--border)] overflow-hidden">
                        @foreach ($hasilCari as $b)
                            <button type="button" wire:click="pilihKaryawan({{ $b->id }})"
                                class="w-full text-left px-3 py-2.5 hover:bg-brand-50 transition flex items-center justify-between gap-2">
                                <span class="text-sm font-medium truncate">{{ $b->nama_lengkap }}</span>
                                <span class="text-xs text-neutral-400 tnum shrink-0">{{ $b->nip }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif (trim($cari) !== '')
                    <div class="text-xs text-neutral-400 mt-2">Tak ada bawahan cocok.</div>
                @endif
            @endif
            @error('karyawanId') <div class="text-danger-600 text-xs mt-2">{{ $message }}</div> @enderror
        </div>
    </section>

    {{-- Daftar usulan yang saya buat --}}
    <section class="card">
        <div class="card-header"><div class="card-title">Usulan Saya</div></div>
        <div class="card-pad">
            @forelse ($usulan as $s)
                <div class="flex items-start justify-between gap-3 py-3 border-b border-[var(--border)] last:border-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="badge badge-warning">{{ $s->tingkat->label() }}</span>
                            <span class="text-sm font-semibold truncate">{{ $s->karyawan->nama_lengkap }}</span>
                        </div>
                        <div class="text-xs text-neutral-400 mt-1 truncate">{{ $s->uraian }}</div>
                    </div>
                    <span class="badge shrink-0
                        @class([
                            'badge-neutral' => $s->status->pending(),
                            'badge-success' => $s->status->value === 'diterbitkan',
                            'badge-danger' => in_array($s->status->value, ['ditolak', 'dicabut'], true),
                        ])">{{ $s->status->label() }}</span>
                </div>
            @empty
                <div class="text-sm text-neutral-400 py-6 text-center">Belum ada usulan sanksi.</div>
            @endforelse
        </div>
    </section>
</div>
