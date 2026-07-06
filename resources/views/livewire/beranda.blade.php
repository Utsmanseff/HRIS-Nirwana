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

    {{-- Kartu Jatah Cuti (siapa pun dengan data karyawan) --}}
    @if ($saldo)
        @if ($saldo->eligible())
            <div class="rounded-xl p-4 sm:p-5 text-white" style="background:var(--panel-glow),var(--panel-grad)">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-white/60 text-xs font-semibold uppercase tracking-wide">Jatah Cuti Tahunan</div>
                        <div class="text-4xl font-extrabold tnum mt-1">{{ $saldo->efektif() }}<span class="text-lg text-white/50 font-bold"> hari tersisa</span></div>
                    </div>
                    <a href="{{ route('cuti') }}" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:0">Kelola cuti</a>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                    <div class="rounded-lg bg-white/10 py-2"><div class="text-[10px] text-white/55 font-semibold">JATAH</div><div class="font-bold tnum">{{ $saldo->jatah() }}</div></div>
                    <div class="rounded-lg bg-white/10 py-2"><div class="text-[10px] text-white/55 font-semibold">TERPAKAI</div><div class="font-bold tnum">{{ $saldo->terpakai() }}</div></div>
                    <div class="rounded-lg bg-white/10 py-2"><div class="text-[10px] text-white/55 font-semibold">PENDING</div><div class="font-bold tnum" style="color:#fcd34d">{{ $saldo->pending() }}</div></div>
                </div>
            </div>
        @else
            <div class="card card-pad">
                <div class="text-sm font-semibold mb-1">Jatah Cuti Tahunan</div>
                <p class="text-sm text-neutral-400">Belum berhak cuti tahunan (masa kerja belum genap 1 tahun). Masih bisa ajukan izin, sakit, atau melahirkan lewat <a href="{{ route('cuti') }}" class="font-semibold" style="color:var(--brand-600)">halaman Cuti</a>.</p>
            </div>
        @endif
    @endif

    {{-- Kartu pending cuti org-wide (HRD) --}}
    @if(! empty($bisaKelolaCuti))
        <a href="{{ route('cuti.laporan') }}" class="card card-pad block hover:shadow-md transition">
            <div class="field-label text-warning-700">Pending Cuti</div>
            <div class="text-2xl font-bold tnum">{{ $cutiPending }}</div>
            <div class="text-xs text-neutral-500 mt-1">Menunggu persetujuan · lihat laporan</div>
        </a>
    @endif

    {{-- Grid menu (gate-permission). Tile placeholder (modul belum ada) diredupkan. --}}
    <div>
        <div class="text-[13px] font-bold text-neutral-700 mb-3">Menu</div>
        <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach ($menu as $it)
                @continue($it['id'] === 'beranda')
                @php $placeholder = $it['route'] === null; @endphp
                <a href="{{ \App\Support\NavMenu::href($it) }}"
                   @class(['tile', 'opacity-40 pointer-events-none' => $placeholder])
                   @if ($placeholder) aria-disabled="true" @endif>
                    <span class="tile-ic bg-brand-50 text-brand-600"><x-icon :name="$it['icon']" :size="22" /></span>
                    <span class="text-[11px] font-semibold leading-tight">{{ $it['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

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
                    <div class="flex gap-1.5">
                        <a href="{{ route('sdm.laporan.pengingat') }}?format=xlsx" class="btn btn-ghost btn-sm">Excel</a>
                        <a href="{{ route('sdm.laporan.pengingat') }}?format=pdf" class="btn btn-ghost btn-sm">PDF</a>
                        <a href="{{ route('sdm.karyawan') }}" class="btn btn-ghost btn-sm">Lihat semua</a>
                    </div>
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
                        <a href="{{ route('sdm.laporan.karyawan') }}?format=xlsx" class="p-3 rounded-lg border border-neutral-200 text-[13px] font-semibold hover:border-brand-400">Ekspor laporan</a>
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
