<div class="space-y-6 rise">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Selamat datang, {{ auth()->user()->karyawan?->nama_lengkap ?? auth()->user()->name }} 👋</h1>
            <p class="text-neutral-500 text-sm mt-1">
                {{ now()->locale('id')->translatedFormat('l, j F Y') }}@if($bisaSdm) · {{ $totalPerhatian }} hal butuh perhatian di SDM.@endif
            </p>
        </div>
        @if ($bisaSdm)
            <a href="{{ route('sdm.karyawan.tambah') }}" class="btn btn-primary">+ Tambah Karyawan</a>
        @endif
    </div>

    @unless ($bisaSdm)
        <div class="card card-pad">
            <p class="text-sm text-neutral-500">Lihat dan kelola data dirimu di halaman <a href="{{ route('profil') }}" class="font-semibold" style="color:var(--brand-600)">Profil</a>.</p>
        </div>
    @endunless

    @if ($bisaSdm)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card card-pad">
                <div class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">Karyawan Aktif</div>
                <div class="text-3xl font-extrabold tracking-tight font-mono">{{ $jumlahAktif }}</div>
            </div>
            <div class="card card-pad">
                <div class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">Kontrak ≤ 30 Hari</div>
                <div class="text-3xl font-extrabold tracking-tight font-mono" style="color:var(--warning-600)">{{ $jumlahAkanBerakhir }}</div>
                <div class="text-xs text-neutral-400 font-medium mt-1">perlu ditinjau</div>
            </div>
            <div class="card card-pad">
                <div class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">Terlewat</div>
                <div class="text-3xl font-extrabold tracking-tight font-mono" style="color:var(--danger-500)">{{ $jumlahTerlewat }}</div>
                <div class="text-xs font-medium mt-1" style="color:var(--danger-500)">butuh tindakan segera</div>
            </div>
            <div class="card card-pad">
                <div class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">Belum Tetap</div>
                <div class="text-3xl font-extrabold tracking-tight font-mono">{{ $jumlahBelumTetap }}</div>
                <div class="text-xs text-neutral-400 font-medium mt-1">PKWT / percobaan</div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 card h-fit">
                <div class="card-header">
                    <div>
                        <div class="card-title">Pengingat Kontrak</div>
                        <div class="text-xs text-neutral-400 mt-0.5">Diturunkan dari kontrak terakhir tiap karyawan.</div>
                    </div>
                    <a href="{{ route('sdm.karyawan') }}" class="btn btn-ghost btn-sm">Lihat semua</a>
                </div>
                @if ($pengingatKontrak->isEmpty())
                    <p class="card-pad text-sm text-neutral-400">Tidak ada kontrak yang butuh perhatian. 🎉</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-neutral-400 border-b border-neutral-200">
                                <th class="px-4 py-2.5 font-semibold">Karyawan</th>
                                <th class="px-4 py-2.5 font-semibold">Tahap</th>
                                <th class="px-4 py-2.5 font-semibold">Berakhir</th>
                                <th class="px-4 py-2.5 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pengingatKontrak as $p)
                                <tr class="border-b border-neutral-100 last:border-0">
                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('sdm.karyawan.detail', $p->karyawan) }}?tab=kontrak" class="font-semibold hover:underline">{{ $p->karyawan->nama_lengkap }}</a>
                                        <div class="font-mono text-xs text-neutral-400">{{ $p->karyawan->nip }}</div>
                                    </td>
                                    <td class="px-4 py-2.5">{{ $p->kontrak->jenis->label() }}</td>
                                    <td class="px-4 py-2.5 font-mono text-neutral-500">{{ $p->kontrak->tanggal_akhir->translatedFormat('j M Y') }}</td>
                                    <td class="px-4 py-2.5">
                                        @if ($p->sisaHari < 0)
                                            <span class="badge badge-danger">Terlewat · {{ abs($p->sisaHari) }} hari</span>
                                        @else
                                            <span class="badge badge-warning">Akan berakhir · H-{{ $p->sisaHari }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="space-y-6">
                <div class="card h-fit">
                    <div class="card-header"><div class="card-title">Aksi Cepat</div></div>
                    <div class="card-pad grid grid-cols-2 gap-2.5">
                        <a href="{{ route('sdm.karyawan.tambah') }}" class="p-3 rounded-lg border border-neutral-200 text-[13px] font-semibold hover:border-brand-400">+ Karyawan baru</a>
                        <a href="{{ route('sdm.struktur') }}" class="p-3 rounded-lg border border-neutral-200 text-[13px] font-semibold hover:border-brand-400">Atur organisasi</a>
                        <a href="{{ route('sdm.karyawan') }}" class="p-3 rounded-lg border border-neutral-200 text-[13px] font-semibold hover:border-brand-400">Ekspor laporan</a>
                        @can('kelola-rbac')
                            <a href="{{ route('sistem.pengguna') }}" class="p-3 rounded-lg border border-neutral-200 text-[13px] font-semibold hover:border-brand-400">Kelola role</a>
                        @endcan
                    </div>
                </div>

                @if ($pengingatSip->isNotEmpty())
                    <div class="card h-fit">
                        <div class="card-header"><div class="card-title">Pengingat SIP</div></div>
                        <div class="card-pad space-y-3">
                            @foreach ($pengingatSip as $p)
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <a href="{{ route('sdm.karyawan.detail', $p->karyawan) }}" class="text-sm font-semibold hover:underline">{{ $p->karyawan->nama_lengkap }}</a>
                                        <div class="text-xs text-neutral-400 font-mono">{{ $p->karyawan->sip_berlaku_akhir->translatedFormat('j M Y') }}</div>
                                    </div>
                                    @if ($p->sisaHari < 0)
                                        <span class="badge badge-danger">Habis</span>
                                    @else
                                        <span class="badge badge-warning">H-{{ $p->sisaHari }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
