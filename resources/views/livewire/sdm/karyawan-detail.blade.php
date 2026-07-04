<div class="space-y-6 rise">
    {{-- Header profil --}}
    <div class="card overflow-hidden">
        <div class="h-20" style="background:var(--panel-glow),var(--panel-grad)"></div>
        <div class="px-6 pb-5">
            {{-- Hanya avatar yang overlap band gelap; teks mulai DI BAWAH band supaya terbaca di tema light. --}}
            <div class="flex flex-wrap items-start gap-4">
                <span class="avatar w-20 h-20 text-2xl ring-4 shadow-sm shrink-0 -mt-10" style="background:var(--brand-100);color:var(--brand-700);--tw-ring-color:var(--bg-surface)">{{ $this->inisial() }}</span>
                <div class="flex-1 min-w-[200px] pt-1">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="text-xl font-extrabold tracking-tight">{{ $karyawan->nama_lengkap }}</h2>
                        @if ($karyawan->status->value === 'aktif')
                            <span class="badge badge-success"><span class="dot"></span>Aktif</span>
                        @else
                            <span class="badge badge-neutral">Nonaktif</span>
                        @endif
                        @if ($karyawan->kontrakTerbaru)
                            <span class="badge {{ $karyawan->kontrakTerbaru->jenis->value === 'tetap' ? 'badge-brand' : 'badge-info' }}">{{ $karyawan->kontrakTerbaru->jenis->label() }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-neutral-500 mt-0.5">
                        {{ $karyawan->jabatan->nama }} · {{ $karyawan->orgUnit->nama }}@if ($karyawan->orgUnit->parent) · {{ $karyawan->orgUnit->parent->nama }}@endif ·
                        <span class="font-mono">NIP {{ $karyawan->nip }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <a href="{{ route('sdm.karyawan.ubah', $karyawan) }}" class="btn btn-secondary btn-sm">Ubah</a>
                    @if ($karyawan->status->value === 'aktif')
                        <button wire:click="formNonaktif" class="btn btn-ghost btn-sm" style="color:var(--danger-600)">Nonaktifkan</button>
                    @else
                        <button wire:click="aktifkanLagi" class="btn btn-secondary btn-sm">Aktifkan lagi</button>
                    @endif
                </div>
            </div>
        </div>
        <div class="px-4 sm:px-6 flex gap-1 border-t border-neutral-100 overflow-x-auto whitespace-nowrap">
            @foreach (['profil' => 'Profil', 'kontrak' => 'Kontrak & Pengingat', 'dokumen' => 'Dokumen', 'akun' => 'Akun & Role'] as $id => $labelTab)
                <button wire:click="$set('tab', '{{ $id }}')" class="tab-btn shrink-0 {{ $tab === $id ? 'on' : '' }}">{{ $labelTab }}</button>
            @endforeach
        </div>
    </div>

    @if ($showNonaktif)
        <div class="card card-pad space-y-3" style="border-color:var(--danger-100)">
            <div class="card-title">Nonaktifkan Karyawan</div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="field-label">Alasan *</label>
                    <select wire:model="alasanNonaktif" class="input @error('alasanNonaktif') input-error @enderror">
                        <option value="">— Pilih alasan —</option>
                        <option value="resign">Resign</option>
                        <option value="kontrak_berakhir">Kontrak berakhir</option>
                        <option value="phk">PHK</option>
                        <option value="pensiun">Pensiun</option>
                        <option value="meninggal">Meninggal</option>
                    </select>
                    @error('alasanNonaktif') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Tanggal Nonaktif *</label>
                    <input type="date" wire:model="tanggalNonaktif" class="input @error('tanggalNonaktif') input-error @enderror">
                    @error('tanggalNonaktif') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-neutral-400">Karyawan nonaktif hilang dari daftar default (filter Aktif) dan tidak muncul di pengingat kontrak/SIP.</p>
            <div class="flex gap-2">
                <button wire:click="nonaktifkan" class="btn btn-primary btn-sm">Nonaktifkan</button>
                <button wire:click="batalNonaktif" class="btn btn-ghost btn-sm">Batal</button>
            </div>
        </div>
    @endif

    {{-- TAB: Profil --}}
    @if ($tab === 'profil')
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 card h-fit">
                <div class="card-header"><div class="card-title">Data Pribadi</div></div>
                <dl class="card-pad grid sm:grid-cols-3 gap-y-5 gap-x-4 text-sm">
                    <div><dt class="text-neutral-500 text-xs">NIK</dt><dd class="font-mono font-semibold">{{ $karyawan->nik ?? '—' }}</dd></div>
                    <div><dt class="text-neutral-500 text-xs">Tempat, Tgl Lahir</dt><dd class="font-semibold">{{ $karyawan->tempat_lahir ?? '—' }}{{ $karyawan->tanggal_lahir ? ', '.$karyawan->tanggal_lahir->translatedFormat('j M Y') : '' }}</dd></div>
                    <div><dt class="text-neutral-500 text-xs">Jenis Kelamin</dt><dd class="font-semibold">{{ $karyawan->jenis_kelamin?->value === 'L' ? 'Laki-laki' : ($karyawan->jenis_kelamin?->value === 'P' ? 'Perempuan' : '—') }}</dd></div>
                    <div><dt class="text-neutral-500 text-xs">Agama</dt><dd class="font-semibold">{{ $karyawan->agama ?? '—' }}</dd></div>
                    <div><dt class="text-neutral-500 text-xs">Status Nikah</dt><dd class="font-semibold">{{ $karyawan->status_nikah ? ucfirst($karyawan->status_nikah->value) : '—' }}</dd></div>
                    <div><dt class="text-neutral-500 text-xs">Pendidikan Terakhir</dt><dd class="font-semibold">{{ $karyawan->pendidikan_terakhir ?? '—' }}</dd></div>
                    <div class="sm:col-span-3"><dt class="text-neutral-500 text-xs">Alamat</dt><dd class="font-semibold">{{ $karyawan->alamat ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="space-y-6">
                <div class="card">
                    <div class="card-header"><div class="card-title">Kontak</div></div>
                    <dl class="card-pad space-y-4 text-sm">
                        <div><dt class="text-neutral-500 text-xs">No. HP</dt><dd class="font-mono font-semibold">{{ $karyawan->no_hp ?? '—' }}</dd></div>
                        <div><dt class="text-neutral-500 text-xs">Email Pribadi</dt><dd class="font-semibold">{{ $karyawan->email ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="card">
                    <div class="card-header"><div class="card-title">Kepegawaian</div></div>
                    <dl class="card-pad space-y-4 text-sm">
                        <div><dt class="text-neutral-500 text-xs">Tanggal Masuk</dt><dd class="font-semibold">{{ $karyawan->tanggal_masuk?->translatedFormat('j M Y') ?? '—' }}</dd></div>
                        @php $atasan = $karyawan->atasanDerived(); @endphp
                        <div><dt class="text-neutral-500 text-xs">Atasan Langsung</dt><dd class="font-semibold">{{ $atasan?->nama_lengkap ?? '—' }}@if ($atasan?->jabatan) · {{ $atasan->jabatan->nama }}@endif</dd></div>
                        <div><dt class="text-neutral-500 text-xs">Level Jabatan</dt><dd class="font-semibold">L{{ $karyawan->jabatan->level->value }} · {{ ucfirst($karyawan->jabatan->level->name) }}</dd></div>
                        @if ($karyawan->sip_nomor)
                            <div><dt class="text-neutral-500 text-xs">Nomor SIP</dt><dd class="font-mono font-semibold">{{ $karyawan->sip_nomor }}</dd></div>
                            <div><dt class="text-neutral-500 text-xs">Masa Berlaku SIP</dt><dd class="font-semibold tnum">{{ $karyawan->sip_berlaku_mulai?->translatedFormat('j M Y') }} → {{ $karyawan->sip_berlaku_akhir?->translatedFormat('j M Y') }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB: Kontrak & Pengingat --}}
    @if ($tab === 'kontrak')
        @php $pengingat = $this->pengingatKontrak(); $terbaru = $karyawan->kontrakTerbaru; @endphp
        @if ($pengingat)
            @if ($pengingat->severity->value === 'terlewat')
                <div class="flex gap-3 p-4 rounded-lg border bg-danger-50 border-danger-100">
                    <div class="text-sm text-danger-700"><b>Kontrak terlewat.</b> {{ $terbaru->jenis->label() }} berakhir {{ $terbaru->tanggal_akhir->translatedFormat('j M Y') }} ({{ abs($pengingat->sisaHari) }} hari lalu) — segera tindak lanjut.</div>
                </div>
            @else
                <div class="flex gap-3 p-4 rounded-lg border bg-warning-50 border-warning-100">
                    <div class="text-sm text-warning-700"><b>Segera berakhir.</b> {{ $terbaru->jenis->label() }} berakhir {{ $terbaru->tanggal_akhir->translatedFormat('j M Y') }} (H-{{ $pengingat->sisaHari }}).</div>
                </div>
            @endif
        @elseif ($terbaru && $terbaru->jenis->value === 'tetap')
            <div class="flex gap-3 p-4 rounded-lg border bg-success-50 border-success-100">
                <div class="text-sm text-success-700"><b>Karyawan tetap.</b> Kontrak terakhir tanpa batas waktu — tidak ada pengingat aktif.</div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><div><div class="card-title">Riwayat Kontrak</div><div class="text-xs text-neutral-400 mt-0.5">Tiap tahap & perpanjangan = baris baru, tidak menimpa.</div></div><button wire:click="formKontrakBaru" class="btn btn-secondary btn-sm">+ Tambah tahap</button></div>
            @if ($showFormKontrak)
                <div class="card-pad border-b border-neutral-100 space-y-3">
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <label class="field-label">Jenis *</label>
                            <select wire:model.live="kJenis" class="input">
                                @foreach (\App\Enums\JenisKontrak::cases() as $jk)
                                    <option value="{{ $jk->value }}">{{ $jk->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Tanggal Mulai *</label>
                            <input type="date" wire:model="kMulai" class="input @error('kMulai') input-error @enderror">
                            @error('kMulai') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Tanggal Akhir {{ $kJenis === 'tetap' ? '' : '*' }}</label>
                            <input type="date" wire:model="kAkhir" class="input @error('kAkhir') input-error @enderror" @disabled($kJenis === 'tetap')>
                            @error('kAkhir') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-3">
                            <label class="field-label">Keterangan</label>
                            <input wire:model="kKeterangan" class="input" placeholder="mis. Perpanjangan ke-2 / Lolos review divisi">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="simpanKontrak" class="btn btn-primary btn-sm">Simpan Tahap</button>
                        <button wire:click="batalKontrak" class="btn btn-ghost btn-sm">Batal</button>
                    </div>
                </div>
            @endif
            <div class="card-pad">
                @if ($this->riwayatKontrak()->isEmpty())
                    <p class="text-sm text-neutral-400 py-4 text-center">Belum ada riwayat kontrak.</p>
                @else
                    <div class="relative pl-9 space-y-6">
                        @foreach ($this->riwayatKontrak() as $kt)
                            <div class="relative">
                                <span class="absolute -left-9 top-0.5 w-6 h-6 rounded-full {{ $loop->first ? 'bg-brand-500' : 'bg-neutral-200' }} ring-4 ring-white"></span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge {{ $kt->jenis->value === 'tetap' ? 'badge-brand' : ($kt->jenis->value === 'pkwt' ? 'badge-info' : 'badge-neutral') }}">{{ $kt->jenis->label() }}</span>
                                    @if ($loop->first)<span class="text-xs text-neutral-400">terbaru</span>@endif
                                    @if ($kt->id === $this->idAnchorCuti())<span class="text-xs text-brand-600">· anchor hak cuti tahunan</span>@endif
                                </div>
                                <div class="text-xs text-neutral-500 tnum mt-1">
                                    Mulai {{ $kt->tanggal_mulai->translatedFormat('j M Y') }}
                                    @if ($kt->tanggal_akhir) → {{ $kt->tanggal_akhir->translatedFormat('j M Y') }} @else · tanpa tanggal akhir @endif
                                </div>
                                @if ($kt->keterangan)<div class="text-xs text-neutral-400 mt-0.5">{{ $kt->keterangan }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- TAB: Dokumen --}}
    @if ($tab === 'dokumen')
        <div class="card">
            <div class="card-header"><div class="card-title">Dokumen Kepegawaian</div></div>
            <div class="card-pad border-b border-neutral-100">
                <div class="grid sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <label class="field-label">Tipe *</label>
                        <select wire:model="tipeDokumen" class="input @error('tipeDokumen') input-error @enderror">
                            <option value="">— Pilih tipe —</option>
                            <option value="ktp">KTP</option><option value="ijazah">Ijazah</option>
                            <option value="kontrak">Kontrak</option><option value="sip">SIP</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        @error('tipeDokumen') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Berkas (jpg/png/webp/pdf, maks 5 MB) *</label>
                        <input type="file" wire:model="berkas" class="input @error('berkas') input-error @enderror">
                        @error('berkas') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <button wire:click="unggahDokumen" wire:loading.attr="disabled" class="btn btn-primary btn-sm">Unggah</button>
                        <span wire:loading wire:target="berkas" class="text-xs text-neutral-400 ml-2">Memuat…</span>
                    </div>
                </div>
            </div>
            <div class="card-pad">
                @if ($karyawan->dokumen->isEmpty())
                    <p class="text-sm text-neutral-400 py-4 text-center">Belum ada dokumen.</p>
                @else
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach ($karyawan->dokumen as $dok)
                            @php $pdf = $dok->mime === 'application/pdf'; @endphp
                            <div class="rounded-lg border border-neutral-200 overflow-hidden">
                                @if ($pdf)
                                    <a href="{{ route('sdm.dokumen.lihat', $dok) }}" target="_blank" rel="noopener"
                                        class="flex items-center justify-center h-28 bg-danger-50 text-danger-600 font-bold text-xs">PDF</a>
                                @else
                                    <button type="button" class="block w-full h-28 bg-neutral-100"
                                        x-on:click="$dispatch('buka-lightbox', { src: '{{ route('sdm.dokumen.lihat', $dok) }}' })">
                                        <img src="{{ route('sdm.dokumen.lihat', $dok) }}" alt="{{ ucfirst($dok->tipe) }}"
                                            class="w-full h-28 object-cover" loading="lazy">
                                    </button>
                                @endif
                                <div class="p-2.5 flex items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold truncate">{{ ucfirst($dok->tipe) }}</div>
                                        <div class="text-xs text-neutral-400">{{ $this->ukuranBaca($dok->ukuran) }} · {{ $dok->created_at->translatedFormat('j M Y') }}</div>
                                    </div>
                                    <a href="{{ route('sdm.dokumen.unduh', $dok) }}" class="btn btn-ghost btn-icon btn-sm" title="Unduh">↓</a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Lightbox gambar (Alpine bundled Livewire). style display:none = anti-flash sebelum init. --}}
                    <div x-data="{ open: false, src: '' }"
                        x-on:buka-lightbox.window="open = true; src = $event.detail.src"
                        x-show="open" style="display:none"
                        x-on:keydown.escape.window="open = false"
                        x-on:click="open = false"
                        class="fixed inset-0 z-50 bg-black/70 grid place-items-center p-6">
                        <img :src="src" class="max-h-[85vh] max-w-full rounded-lg shadow-2xl" x-on:click.stop alt="preview">
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- TAB: Akun & Role --}}
    @if ($tab === 'akun')
        @if (! $karyawan->user)
            <div class="card card-pad">
                <p class="text-sm text-neutral-500"><b>Belum tertaut akun.</b> Karyawan ini belum punya akun login — akun terbentuk saat karyawan login Google lalu mengklaim datanya.</p>
            </div>
        @else
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 card h-fit">
                    <div class="card-header"><div class="card-title">Role &amp; Hak Akses</div></div>
                    <div class="card-pad space-y-3">
                        @forelse ($karyawan->user->roles as $role)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-neutral-50 border border-neutral-200">
                                <div class="text-sm font-semibold">{{ $role->name }}</div>
                                <span class="badge badge-success">Aktif</span>
                            </div>
                        @empty
                            <p class="text-sm text-neutral-400">Belum punya role.</p>
                        @endforelse
                        <p class="text-xs text-neutral-400 pt-1">Multi-role: hak akses = gabungan semua role.</p>
                        @can('kelola-rbac')
                            <a href="{{ route('sistem.pengguna', ['q' => $karyawan->nip]) }}" class="btn btn-secondary btn-sm">Kelola Role &amp; Akun</a>
                        @endcan
                    </div>
                </div>
                <div class="card h-fit">
                    <div class="card-header"><div class="card-title">Akun Login</div></div>
                    <dl class="card-pad space-y-4 text-sm">
                        <div><dt class="text-neutral-500 text-xs">Username (NIP)</dt><dd class="font-mono font-semibold">{{ $karyawan->nip }}</dd></div>
                        <div><dt class="text-neutral-500 text-xs">Email Akun</dt><dd class="font-semibold">{{ $karyawan->user->email }}</dd></div>
                        <div><dt class="text-neutral-500 text-xs">Login Google</dt><dd class="font-semibold">{{ $karyawan->user->google_id ? 'Tertaut' : 'Belum' }}</dd></div>
                    </dl>
                </div>
            </div>
        @endif
    @endif
</div>
