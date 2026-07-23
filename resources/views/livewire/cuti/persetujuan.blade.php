<div class="mx-auto max-w-6xl">
    <h1 class="text-xl font-bold mb-4">Persetujuan Cuti</h1>

    <div class="card">
        <div class="px-4 flex gap-1 border-b border-neutral-100">
            <button wire:click="$set('tab','perlu-aksi')"
                class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='perlu-aksi' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                Perlu Aksi Saya
                @if($perluAksi->isNotEmpty())
                    <span class="ml-1 inline-grid place-items-center w-5 h-5 rounded-full bg-warning-500 text-white text-[11px]">{{ $perluAksi->count() }}</span>
                @endif
            </button>
            @if($bolehSemua)
                <button wire:click="$set('tab','semua')"
                    class="px-4 py-3 text-sm font-medium border-b-2 {{ $tab==='semua' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                    Semua Pengajuan
                </button>
            @endif
            <button wire:click="$set('tab','kalender')"
                class="px-4 py-3 text-sm font-medium border-b-2 {{ $tab==='kalender' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                Kalender Tim
            </button>
        </div>

        @if($tab==='perlu-aksi')
            @if($perluAksi->isEmpty())
                <div class="card-pad text-sm text-neutral-500">Tak ada pengajuan yang menunggu aksi Anda.</div>
            @else
                <table class="table rtable">
                    <thead><tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Hari</th><th>Tahap</th><th></th></tr></thead>
                    <tbody>
                        @foreach($perluAksi as $p)
                            <tr wire:key="pa-{{ $p->id }}">
                                <td data-primary>
                                    <div class="font-semibold">{{ $p->karyawan->nama_lengkap }}</div>
                                    <div class="text-xs text-neutral-400">{{ $p->karyawan->jabatan?->nama }}</div>
                                </td>
                                <td data-label="Jenis">{{ $p->jenisCuti->nama }}</td>
                                <td data-label="Tanggal" class="tnum text-neutral-600">{{ $p->tanggal_mulai->format('d M') }}–{{ $p->tanggal_selesai->format('d M') }}</td>
                                <td data-label="Hari" class="tnum font-semibold">{{ $p->jumlah_hari }}</td>
                                <td data-label="Tahap">{{ ucfirst($p->tahapAktif()?->peran?->value ?? '') }}</td>
                                <td class="text-right">
                                    <button wire:click="tinjau({{ $p->id }})" class="btn btn-secondary btn-sm">Tinjau</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        @if($tab==='semua' && $bolehSemua)
            <div class="p-4 flex flex-wrap gap-2.5 border-b border-neutral-100">
                <input wire:model.live.debounce.400ms="cari" class="input flex-1 min-w-[200px]" placeholder="Cari nama / NIP…">
                <select wire:model.live="filterJenis" class="select w-auto">
                    <option value="">Semua Jenis</option>
                    @foreach($jenisOpsi as $j)<option value="{{ $j->id }}">{{ $j->nama }}</option>@endforeach
                </select>
                <select wire:model.live="filterStatus" class="select w-auto">
                    <option value="">Semua Status</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="diproses">Diproses</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <table class="table rtable">
                <thead><tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Hari</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($semua as $p)
                        <tr wire:key="sm-{{ $p->id }}">
                            <td data-primary><div class="font-semibold">{{ $p->karyawan->nama_lengkap }}</div><div class="text-xs text-neutral-400 font-mono">{{ $p->karyawan->nip }}</div></td>
                            <td data-label="Jenis">{{ $p->jenisCuti->nama }}</td>
                            <td data-label="Tanggal" class="tnum text-neutral-600">{{ $p->tanggal_mulai->format('d M') }}–{{ $p->tanggal_selesai->format('d M') }}</td>
                            <td data-label="Hari" class="tnum">{{ $p->jumlah_hari }}</td>
                            <td data-label="Status"><span class="badge">{{ ucfirst($p->status->value) }}</span></td>
                            <td class="text-right">
                                @if($p->status->value === 'disetujui')
                                    <button wire:click="mulaiBatal({{ $p->id }})" class="btn btn-secondary btn-sm text-danger-600">Batalkan</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="card-pad text-sm text-neutral-500">Tak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if($tab==='kalender')
            <div class="card-pad">
                <livewire:cuti.kalender-tim />
            </div>
        @endif
    </div>

    {{-- Slide-over review --}}
    @if($tinjauan)
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0" style="background:rgba(8,12,11,.55)" wire:click="tutup"></div>
            <div class="absolute right-0 top-0 h-full w-full max-w-md overflow-y-auto" style="background:var(--bg-surface);box-shadow:var(--shadow-lg)">
                <div class="card-header sticky top-0" style="background:var(--bg-surface)">
                    <div class="card-title">Tinjau Pengajuan Cuti</div>
                    <button class="btn btn-ghost btn-icon" wire:click="tutup"><x-icon name="back" :size="18" /></button>
                </div>
                <div class="p-5 space-y-5">
                    <div>
                        <div class="font-bold">{{ $tinjauan->karyawan->nama_lengkap }}</div>
                        <div class="text-xs text-neutral-400">{{ $tinjauan->karyawan->jabatan?->nama }} · <span class="font-mono">{{ $tinjauan->karyawan->nip }}</span></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3"><div class="text-xs text-neutral-400">Jenis</div><div class="font-semibold">{{ $tinjauan->jenisCuti->nama }}</div></div>
                        <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3"><div class="text-xs text-neutral-400">Tanggal</div><div class="font-semibold tnum">{{ $tinjauan->tanggal_mulai->format('d M') }}–{{ $tinjauan->tanggal_selesai->format('d M Y') }}</div></div>
                        <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3"><div class="text-xs text-neutral-400">Jumlah hari</div><div class="font-semibold tnum">{{ $tinjauan->jumlah_hari }}</div></div>
                        <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3"><div class="text-xs text-neutral-400">Tahap saya</div><div class="font-semibold">{{ ucfirst($tinjauan->tahapAktif()?->peran?->value ?? '') }}</div></div>
                    </div>
                    @if($tinjauan->alasan)
                        <div><div class="text-xs text-neutral-400 mb-1">Alasan</div><div class="text-sm">{{ $tinjauan->alasan }}</div></div>
                    @endif
                    @if($tinjauan->lampiran_path)
                        <a href="{{ route('cuti.lampiran', $tinjauan) }}" target="_blank" class="text-sm text-brand-600 underline">Lihat lampiran</a>
                    @endif

                    @if($saldoTinjau)
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3.5">
                            <div class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2.5">Jatah Cuti Tahunan</div>
                            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                                <div><div class="text-neutral-400">Jatah</div><div class="font-bold tnum">{{ $saldoTinjau['jatah'] }}</div></div>
                                <div><div class="text-neutral-400">Terpakai</div><div class="font-bold tnum">{{ $saldoTinjau['terpakai'] }}</div></div>
                                <div><div class="text-neutral-400">Diminta</div><div class="font-bold tnum">{{ $saldoTinjau['diminta'] }}</div></div>
                                <div><div class="text-neutral-400">Sisa stlh acc</div><div class="font-bold tnum {{ $saldoTinjau['sisa'] < 0 ? 'text-danger-600' : '' }}">{{ $saldoTinjau['sisa'] }}</div></div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <div class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">Alur Persetujuan</div>
                        <div class="space-y-2 text-sm">
                            @foreach($tinjauan->approval as $a)
                                <div class="flex items-center justify-between">
                                    <span>{{ ucfirst($a->peran->value) }} — {{ $a->approver->nama_lengkap }}</span>
                                    <span class="badge {{ $a->status->value === 'setuju' ? 'badge-success' : ($a->status->value === 'tolak' ? 'badge-danger' : 'badge-warning') }}">{{ $a->status->value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($bolehSetPengganti)
                        <div class="rounded-lg border border-neutral-200 p-3 space-y-2">
                            <div class="text-sm font-semibold">Pengganti selama cuti</div>
                            @forelse ($penggantiTinjau as $pg)
                                <div wire:key="pg-{{ $pg->id }}" class="text-sm flex justify-between">
                                    <span>{{ $pg->karyawan->nama_lengkap }}</span>
                                    <span class="text-xs text-neutral-400 tnum">
                                        {{ $pg->tanggal_mulai->format('d M') }} s/d {{ $pg->tanggal_selesai->format('d M Y') }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-xs text-neutral-500">Belum ada pengganti.</p>
                            @endforelse

                            <input type="text" wire:model.live.debounce.400ms="cariPengganti" class="input"
                                placeholder="Cari nama atau NIP…">
                            @foreach ($hasilCariPengganti as $kandidat)
                                <button type="button" wire:key="pgc-{{ $kandidat->id }}"
                                    wire:click="setPengganti({{ $kandidat->id }})"
                                    class="w-full text-left px-3 py-2 rounded-md hover:bg-neutral-100 text-sm">
                                    {{ $kandidat->nama_lengkap }}
                                    <span class="text-xs text-neutral-400">· {{ $kandidat->nip }}</span>
                                </button>
                            @endforeach
                            @error('cariPengganti') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="field-label">Catatan <span class="text-neutral-400 font-normal">(wajib bila menolak)</span></label>
                        <textarea wire:model="catatan" class="textarea" rows="2" placeholder="Catatan untuk pemohon…"></textarea>
                        @error('catatan') <div class="text-xs text-danger-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-pad sticky bottom-0 border-t border-neutral-100 flex gap-3" style="background:var(--bg-surface)">
                    <button class="btn btn-secondary text-danger-600 flex-1" wire:click="tolak">Tolak</button>
                    <button class="btn btn-primary flex-1" wire:click="setujui">Setujui</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal batal HRD --}}
    @if($batalId)
        <div class="fixed inset-0 z-50 grid place-items-center p-4">
            <div class="absolute inset-0" style="background:rgba(8,12,11,.55)" wire:click="$set('batalId', null)"></div>
            <div class="relative card card-pad w-full max-w-sm space-y-3">
                <div class="font-bold">Batalkan Cuti Disetujui</div>
                <p class="text-xs text-neutral-500">Jatah cuti tahunan akan otomatis kembali. Pemohon diberi tahu.</p>
                <textarea wire:model="alasanBatal" class="textarea" rows="2" placeholder="Alasan pembatalan…"></textarea>
                @error('alasanBatal') <div class="text-xs text-danger-600">{{ $message }}</div> @enderror
                <div class="flex gap-2 justify-end">
                    <button class="btn btn-secondary btn-sm" wire:click="$set('batalId', null)">Batal</button>
                    <button class="btn btn-primary btn-sm" wire:click="konfirmasiBatal">Konfirmasi</button>
                </div>
            </div>
        </div>
    @endif
</div>
