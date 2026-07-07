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

    @if ($karyawanTerpilih)
        {{-- Riwayat sanksi aktif + ladder eskalasi --}}
        <section class="card rise">
            <div class="card-header"><div class="card-title">Riwayat Sanksi Aktif (≤6 bln)</div></div>
            <div class="card-pad">
                @if ($sanksiAktif->isEmpty())
                    <div class="text-sm text-neutral-400 mb-4">Belum ada sanksi aktif — bersih.</div>
                @else
                    <div class="space-y-2 mb-4">
                        @foreach ($sanksiAktif as $sa)
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2"><span class="badge badge-warning">{{ $sa->tingkat->label() }}</span><span class="truncate">{{ $sa->uraian }}</span></span>
                                <span class="text-xs text-neutral-400 tnum shrink-0">{{ optional($sa->tanggal_terbit)->translatedFormat('d M Y') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @php $tertinggi = $sanksiAktif->max(fn ($s) => $s->tingkat->value); @endphp
                <div class="flex items-center gap-1">
                    @foreach ($tingkatOpsi as $t)
                        @php
                            $done = $tertinggi !== null && $t->value <= $tertinggi;
                            $next = $saran && $t === $saran;
                        @endphp
                        @if (! $loop->first)
                            <svg width="11" class="text-neutral-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
                        @endif
                        <span class="flex-1 text-center text-[.65rem] font-bold px-1 py-1.5 rounded border whitespace-nowrap
                            @class([
                                'bg-danger-50 border-danger-100 text-danger-700' => $done,
                                'bg-brand-50 border-brand-500 text-brand-700 ring-1 ring-brand-500' => $next && ! $done,
                                'bg-[var(--bg-surface)] border-[var(--border)] text-neutral-400' => ! $done && ! $next,
                            ])">{{ $t->label() }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Detail pelanggaran + tingkat --}}
        <form wire:submit="simpan" class="space-y-6">
            <section class="card rise">
                <div class="card-header"><div class="card-title">Detail Pelanggaran</div></div>
                <div class="card-pad grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Tanggal kejadian</label>
                        <input type="date" wire:model="tanggalKejadian" max="{{ now()->toDateString() }}" class="input">
                        @error('tanggalKejadian') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label">Uraian kejadian</label>
                        <textarea wire:model="uraian" rows="3" class="textarea" placeholder="Jelaskan kronologi & bukti pendukung…"></textarea>
                        @error('uraian') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="card rise">
                <div class="card-header"><div class="card-title">Tingkat Sanksi Diusulkan</div></div>
                <div class="card-pad">
                    @if ($saran)
                        <div class="flex gap-2.5 p-3 rounded-lg bg-brand-50 border border-brand-100 mb-4 text-xs text-brand-700">
                            <svg width="15" class="shrink-0 mt-px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 16.5 7.1 18.2l.9-5.5-4-3.9 5.5-.8z"/></svg>
                            <span class="leading-relaxed">Sistem menyarankan <b>{{ $saran->label() }}</b> (eskalasi dari sanksi aktif). Untuk pelanggaran berat, boleh lompat — pilih manual.</span>
                        </div>
                    @endif
                    <label class="field-label">Tingkat</label>
                    <select wire:model="tingkat" class="select">
                        @foreach ($tingkatOpsi as $t)
                            <option value="{{ $t->value }}">{{ $t->label() }}{{ $saran && $t === $saran ? ' (disarankan)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('tingkat') <div class="text-danger-600 text-xs mt-1">{{ $message }}</div> @enderror
                    <div class="field-hint">Keputusan final ada di HRD. Usulan ini ditinjau berjenjang dulu.</div>
                </div>
            </section>

            <div class="flex gap-2.5">
                <button type="button" wire:click="batalKaryawan" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary flex-1" wire:loading.attr="disabled">Kirim Usulan</button>
            </div>
        </form>
    @endif

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
