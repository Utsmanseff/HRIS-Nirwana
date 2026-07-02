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

        {{-- Pengingat kontrak + SIP + aksi cepat: Task 4 --}}
    @endif
</div>
