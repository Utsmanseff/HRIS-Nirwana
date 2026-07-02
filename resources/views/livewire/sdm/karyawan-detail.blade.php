<div class="space-y-6 rise">
    {{-- Header profil --}}
    <div class="card overflow-hidden">
        <div class="h-20" style="background:var(--panel-glow),var(--panel-grad)"></div>
        <div class="px-6 pb-5">
            <div class="flex flex-wrap items-end gap-4 -mt-10">
                <span class="avatar w-20 h-20 text-2xl ring-4 ring-white shadow-sm" style="background:var(--brand-100);color:var(--brand-700)">{{ $this->inisial() }}</span>
                <div class="flex-1 min-w-[200px] pb-1">
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
            </div>
        </div>
        <div class="px-4 sm:px-6 flex gap-1 border-t border-neutral-100 overflow-x-auto whitespace-nowrap">
            @foreach (['profil' => 'Profil', 'kontrak' => 'Kontrak & Pengingat', 'dokumen' => 'Dokumen', 'akun' => 'Akun & Role'] as $id => $labelTab)
                <button wire:click="$set('tab', '{{ $id }}')" class="tab-btn shrink-0 {{ $tab === $id ? 'on' : '' }}">{{ $labelTab }}</button>
            @endforeach
        </div>
    </div>

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
                        <div><dt class="text-neutral-500 text-xs">Atasan Langsung</dt><dd class="font-semibold">{{ $karyawan->atasan?->nama_lengkap ?? '—' }}@if ($karyawan->atasan?->jabatan) · {{ $karyawan->atasan->jabatan->nama }}@endif</dd></div>
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
            <div class="card-header"><div><div class="card-title">Riwayat Kontrak</div><div class="text-xs text-neutral-400 mt-0.5">Tiap tahap & perpanjangan = baris baru, tidak menimpa.</div></div></div>
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
            <div class="card-pad">
                @if ($karyawan->dokumen->isEmpty())
                    <p class="text-sm text-neutral-400 py-4 text-center">Belum ada dokumen.</p>
                @else
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($karyawan->dokumen as $dok)
                            @php $pdf = $dok->mime === 'application/pdf'; @endphp
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-neutral-200">
                                <span class="w-10 h-10 rounded-md {{ $pdf ? 'bg-danger-50 text-danger-600' : 'bg-info-50 text-info-600' }} grid place-items-center font-bold text-[10px]">{{ $pdf ? 'PDF' : 'WEBP' }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold truncate">{{ basename($dok->path) }}</div>
                                    <div class="text-xs text-neutral-400">{{ ucfirst($dok->tipe) }} · {{ $this->ukuranBaca($dok->ukuran) }} · {{ $dok->created_at->translatedFormat('j M Y') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- TAB: Akun & Role --}}
    @if ($tab === 'akun')
        @if (! $karyawan->user)
            <div class="card card-pad">
                <p class="text-sm text-neutral-500"><b>Belum tertaut akun.</b> Karyawan ini belum punya akun login — akun terbentuk saat karyawan login Google lalu klaim data, atau dibuat lewat Kelola Pengguna (menyusul).</p>
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
                        <p class="text-xs text-neutral-400 pt-1">Multi-role: hak akses = gabungan semua role. Kelola role di menu Pengguna &amp; Role (menyusul).</p>
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
