<div class="space-y-4 rise" style="max-width:720px">
    {{-- Identitas --}}
    <div class="card card-pad flex items-center gap-4">
        <span class="avatar w-16 h-16 text-xl" style="background:var(--brand-200);color:var(--brand-800)">
            {{ \Illuminate\Support\Str::of($karyawan->nama_lengkap)->explode(' ')->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
        </span>
        <div class="min-w-0">
            <h1 class="text-lg font-extrabold tracking-tight">{{ $karyawan->nama_lengkap }}</h1>
            <p class="text-sm text-neutral-500">{{ $karyawan->jabatan?->nama }} · {{ $karyawan->orgUnit?->nama }}</p>
            <p class="font-mono text-xs text-neutral-400 mt-0.5">{{ $karyawan->nip }}</p>
        </div>
    </div>

    {{-- Data pribadi (read-only) --}}
    <div class="card">
        <div class="card-header"><div class="card-title">Data Pribadi</div></div>
        <dl class="card-pad grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div class="flex justify-between gap-2 border-b border-neutral-100 pb-2"><dt class="text-neutral-500">NIK</dt><dd class="font-mono font-semibold">{{ $karyawan->nik ?: '—' }}</dd></div>
            <div class="flex justify-between gap-2 border-b border-neutral-100 pb-2"><dt class="text-neutral-500">Tempat, Tgl Lahir</dt><dd class="font-semibold text-right">{{ $karyawan->tempat_lahir ?: '—' }}{{ $karyawan->tanggal_lahir ? ', '.$karyawan->tanggal_lahir->translatedFormat('d M Y') : '' }}</dd></div>
            <div class="flex justify-between gap-2 border-b border-neutral-100 pb-2"><dt class="text-neutral-500">Pendidikan</dt><dd class="font-semibold text-right">{{ $karyawan->pendidikan_terakhir ?: '—' }}</dd></div>
            <div class="flex justify-between gap-2 border-b border-neutral-100 pb-2"><dt class="text-neutral-500">Tanggal Masuk</dt><dd class="font-semibold text-right">{{ $karyawan->tanggal_masuk?->translatedFormat('d M Y') ?: '—' }}</dd></div>
        </dl>
    </div>

    {{-- Kepegawaian (read-only) --}}
    <div class="card">
        <div class="card-header"><div class="card-title">Kepegawaian</div></div>
        <dl class="card-pad grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div class="flex justify-between gap-2 border-b border-neutral-100 pb-2"><dt class="text-neutral-500">Unit</dt><dd class="font-semibold text-right">{{ $karyawan->orgUnit?->nama ?: '—' }}</dd></div>
            <div class="flex justify-between gap-2 border-b border-neutral-100 pb-2"><dt class="text-neutral-500">Atasan Langsung</dt><dd class="font-semibold text-right">{{ $karyawan->atasan?->nama_lengkap ?: '—' }}</dd></div>
        </dl>
        <p class="px-4 pb-3 text-[11px] text-neutral-400">Data pribadi &amp; kepegawaian dikelola SDM. Untuk koreksi, hubungi bagian SDM.</p>
    </div>

    {{-- Kontak + kata sandi + notifikasi ditambahkan di Task 5-7 --}}
</div>
