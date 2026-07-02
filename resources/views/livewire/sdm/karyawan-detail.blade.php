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
</div>
