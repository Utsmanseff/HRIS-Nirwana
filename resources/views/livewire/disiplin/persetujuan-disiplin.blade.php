<div class="mx-auto max-w-6xl">
    <h1 class="text-xl font-bold mb-4">Persetujuan Sanksi</h1>

    @if (session('disiplin_ok'))
        <div class="rounded-lg bg-brand-50 border border-brand-100 text-brand-700 text-sm px-4 py-3 mb-4">{{ session('disiplin_ok') }}</div>
    @endif

    <div class="card">
        <div class="px-4 flex gap-1 border-b border-neutral-100">
            <button wire:click="$set('tab','perlu-aksi')"
                class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='perlu-aksi' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                Perlu Aksi Saya
                @if ($perluAksi->isNotEmpty())
                    <span class="ml-1 inline-grid place-items-center w-5 h-5 rounded-full bg-warning-500 text-white text-[11px]">{{ $perluAksi->count() }}</span>
                @endif
            </button>
            @if ($bolehSemua)
                <button wire:click="$set('tab','semua')"
                    class="px-4 py-3 text-sm font-medium border-b-2 {{ $tab==='semua' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                    Semua Sanksi
                </button>
            @endif
        </div>

        @if ($tab === 'perlu-aksi')
            @if ($perluAksi->isEmpty())
                <div class="card-pad text-sm text-neutral-500">Tak ada usulan menunggu aksi Anda.</div>
            @else
                <table class="table rtable">
                    <thead><tr><th>Karyawan</th><th>Tingkat</th><th>Kejadian</th><th>Tahap</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($perluAksi as $s)
                            <tr wire:key="pa-{{ $s->id }}">
                                <td data-primary>
                                    <div class="font-semibold">{{ $s->karyawan->nama_lengkap }}</div>
                                    <div class="text-xs text-neutral-400 truncate max-w-[220px]">{{ $s->uraian }}</div>
                                </td>
                                <td data-label="Tingkat"><span class="badge badge-warning">{{ $s->tingkat->label() }}</span></td>
                                <td data-label="Kejadian" class="tnum text-neutral-600">{{ $s->tanggal_kejadian->format('d M Y') }}</td>
                                <td data-label="Tahap">{{ ucfirst($s->tahapAktif()?->peran?->value ?? '') }}</td>
                                <td class="text-right"><button wire:click="tinjau({{ $s->id }})" class="btn btn-secondary btn-sm">Tinjau</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        @if ($tab === 'semua' && $bolehSemua)
            <div class="p-4 flex flex-wrap gap-2.5 border-b border-neutral-100">
                <input wire:model.live.debounce.400ms="cari" class="input flex-1 min-w-[200px]" placeholder="Cari nama / NIP…">
                <select wire:model.live="filterStatus" class="select w-auto">
                    <option value="">Semua Status</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="diproses">Diproses</option>
                    <option value="diterbitkan">Diterbitkan</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dicabut">Dicabut</option>
                </select>
            </div>
            <table class="table rtable">
                <thead><tr><th>Karyawan</th><th>Tingkat</th><th>Pengusul</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($semua as $s)
                        <tr wire:key="sm-{{ $s->id }}">
                            <td data-primary>
                                <div class="font-semibold">{{ $s->karyawan->nama_lengkap }}</div>
                                <div class="text-xs text-neutral-400 truncate max-w-[220px]">{{ $s->uraian }}</div>
                            </td>
                            <td data-label="Tingkat"><span class="badge badge-warning">{{ $s->tingkat->label() }}</span></td>
                            <td data-label="Pengusul">{{ $s->pengusul->nama_lengkap }}</td>
                            <td data-label="Status"><span class="badge">{{ $s->status->label() }}</span></td>
                            <td class="text-right"><button wire:click="tinjau({{ $s->id }})" class="btn btn-secondary btn-sm">Detail</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="card-pad text-sm text-neutral-500">Tak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    {{-- Slide-over review (aksi diisi Task 2/3/5) --}}
    @if ($tinjauan)
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0" style="background:rgba(8,12,11,.55)" wire:click="tutup"></div>
            <div class="absolute right-0 top-0 h-full w-full max-w-md overflow-y-auto" style="background:var(--bg-surface);box-shadow:var(--shadow-lg)">
                <div class="card-header sticky top-0" style="background:var(--bg-surface)">
                    <div class="card-title">Tinjau Usulan Sanksi</div>
                    <button class="btn btn-ghost btn-icon" wire:click="tutup"><x-icon name="back" :size="18" /></button>
                </div>
                <div class="p-5 space-y-5">
                    <div>
                        <div class="font-bold">{{ $tinjauan->karyawan->nama_lengkap }}</div>
                        <div class="text-xs text-neutral-400">{{ $tinjauan->karyawan->jabatan?->nama }} · <span class="font-mono">{{ $tinjauan->karyawan->nip }}</span></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3"><div class="text-xs text-neutral-400">Tingkat</div><div class="font-semibold">{{ $tinjauan->tingkat->label() }}</div></div>
                        <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3"><div class="text-xs text-neutral-400">Kejadian</div><div class="font-semibold tnum">{{ $tinjauan->tanggal_kejadian->format('d M Y') }}</div></div>
                        <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3 col-span-2"><div class="text-xs text-neutral-400">Pengusul</div><div class="font-semibold">{{ $tinjauan->pengusul->nama_lengkap }}</div></div>
                    </div>
                    <div><div class="text-xs text-neutral-400 mb-1">Uraian</div><div class="text-sm">{{ $tinjauan->uraian }}</div></div>

                    <div>
                        <div class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">Alur Persetujuan</div>
                        <div class="space-y-2 text-sm">
                            @foreach ($tinjauan->approval as $a)
                                <div class="flex items-center justify-between">
                                    <span>{{ ucfirst($a->peran->value) }} — {{ $a->approver->nama_lengkap }}</span>
                                    <span class="badge {{ $a->status->value === 'setuju' ? 'badge-success' : ($a->status->value === 'tolak' ? 'badge-danger' : 'badge-warning') }}">{{ $a->status->value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($tinjauan->status->value === 'diterbitkan' && $tinjauan->surat_path)
                        <a href="{{ route('disiplin.surat', $tinjauan) }}" target="_blank" class="btn btn-secondary w-full">Lihat Surat (PDF)</a>
                    @endif

                    @php $final = $tahapAktif && ! $tinjauan->approval->where('urutan', '>', $tahapAktif->urutan)->where('status', 'menunggu')->count(); @endphp
                    @if ($tahapAktif && $tahapAktif->approver_id === auth()->user()->karyawan_id)
                        <div>
                            <label class="field-label">Catatan <span class="text-neutral-400 font-normal">(wajib bila menolak)</span></label>
                            <textarea wire:model="catatan" class="textarea" rows="2" placeholder="Catatan…"></textarea>
                            @error('catatan') <div class="text-xs text-danger-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                        @if ($final)
                            <div>
                                <label class="field-label">Nomor Surat <span class="text-danger-500">*</span></label>
                                <input type="text" wire:model="nomorSurat" class="input tnum" placeholder="mis. 01.246/HRD/RSUN/VII/2026">
                                <div class="field-hint">Nomor manual sesuai penomoran RSU. Setelah terbit, surat PDF dibuat &amp; karyawan dinotifikasi.</div>
                                @error('nomorSurat') <div class="text-xs text-danger-600 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <button class="btn btn-primary w-full" wire:click="terbitkan">Terbitkan &amp; Buat Surat</button>
                        @else
                            <button class="btn btn-primary w-full" wire:click="setujui">Setujui &amp; Teruskan</button>
                        @endif
                        <button class="btn btn-secondary text-danger-600 w-full" wire:click="tolak">Tolak Usulan</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
