<div class="space-y-6 rise">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight">Kelola Sanksi</h1>
            <p class="text-sm text-neutral-500">Buat sanksi langsung, cabut, dan pantau semua sanksi.</p>
        </div>
        @unless ($showForm)
            <button wire:click="bukaForm" class="btn btn-primary btn-sm">+ Buat Sanksi Langsung</button>
        @endunless
    </div>

    @if (session('disiplin_ok'))
        <div class="card card-pad text-sm" style="border-color:var(--brand-200);color:var(--brand-700)">{{ session('disiplin_ok') }}</div>
    @endif

    @if ($showForm)
        <div class="card card-pad space-y-4">
            <div class="flex items-center justify-between">
                <div class="card-title">Buat Sanksi Langsung</div>
                <button wire:click="tutupForm" class="btn btn-ghost btn-sm">Tutup</button>
            </div>

            {{-- Pilih karyawan --}}
            @if ($karyawanTerpilih)
                <div class="flex items-center justify-between rounded-lg border border-neutral-100 px-3 py-2">
                    <div>
                        <div class="font-semibold">{{ $karyawanTerpilih->nama_lengkap }}</div>
                        <div class="text-xs text-neutral-400 font-mono">NIP {{ $karyawanTerpilih->nip }}</div>
                    </div>
                    <button wire:click="batalKaryawan" class="btn btn-ghost btn-sm">Ganti</button>
                </div>
                @if ($sanksiAktif->isNotEmpty())
                    <div class="text-xs text-warning-700">Punya {{ $sanksiAktif->count() }} sanksi aktif — saran tingkat sudah dieskalasi.</div>
                @endif
            @else
                <div>
                    <label class="field-label">Cari Karyawan (semua unit) *</label>
                    <input type="search" wire:model.live.debounce.300ms="cariKaryawan" class="input" placeholder="Nama / NIP…">
                    @if ($hasilCari->isNotEmpty())
                        <div class="mt-2 border border-neutral-100 rounded-lg divide-y divide-neutral-50">
                            @foreach ($hasilCari as $k)
                                <button type="button" wire:click="pilihKaryawan({{ $k->id }})" class="w-full text-left px-3 py-2 hover:bg-neutral-50">
                                    <span class="font-medium">{{ $k->nama_lengkap }}</span>
                                    <span class="text-xs text-neutral-400 font-mono ml-2">{{ $k->nip }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @error('karyawanId') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Detail sanksi (aktif setelah pilih karyawan) --}}
            @if ($karyawanTerpilih)
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="field-label">Tingkat *</label>
                        <select wire:model="tingkat" class="input @error('tingkat') input-error @enderror">
                            @foreach ($tingkatOpsi as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        @error('tingkat') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Tanggal Kejadian *</label>
                        <input type="date" wire:model="tanggalKejadian" class="input @error('tanggalKejadian') input-error @enderror">
                        @error('tanggalKejadian') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Nomor Surat *</label>
                        <input type="text" wire:model="nomorSurat" class="input @error('nomorSurat') input-error @enderror" placeholder="01.xxx/HRD/RSUN/VII/2026">
                        @error('nomorSurat') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="field-label">Uraian Pelanggaran *</label>
                    <textarea wire:model="uraian" rows="3" class="input @error('uraian') input-error @enderror"></textarea>
                    @error('uraian') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button wire:click="simpan" class="btn btn-primary btn-sm">Buat Sanksi</button>
                    <button wire:click="tutupForm" class="btn btn-ghost btn-sm">Batal</button>
                </div>
                <p class="text-xs text-neutral-400">
                    @if (auth()->user()->hasRole(\App\Enums\Role::Direktur->value))
                        Sebagai Direktur, sanksi langsung terbit &amp; surat dibuat.
                    @else
                        Sanksi diteruskan ke Direktur untuk diterbitkan.
                    @endif
                </p>
            @endif
        </div>
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
