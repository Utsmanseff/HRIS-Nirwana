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

    {{-- Kontak (editable) --}}
    <div class="card">
        <div class="card-header"><div class="card-title">Kontak</div></div>
        <form wire:submit="simpanKontak" class="card-pad space-y-3">
            @if (session('kontak_ok'))
                <p class="text-sm text-brand-600">{{ session('kontak_ok') }}</p>
            @endif
            <div>
                <label class="field-label">No. HP</label>
                <input wire:model="no_hp" class="input font-mono @error('no_hp') input-error @enderror" placeholder="08xxxxxxxxxx">
                @error('no_hp') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Email Pribadi</label>
                <input wire:model="email" type="email" class="input @error('email') input-error @enderror" placeholder="nama@email.com">
                @error('email') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Alamat</label>
                <textarea wire:model="alamat" rows="2" class="input @error('alamat') input-error @enderror" placeholder="Alamat domisili"></textarea>
                @error('alamat') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div class="pt-1"><button type="submit" class="btn btn-primary btn-sm">Simpan Kontak</button></div>
        </form>
        <p class="px-4 pb-3 text-[11px] text-neutral-400">Hanya kontak &amp; alamat yang bisa kamu ubah. Data lain → hubungi SDM.</p>
    </div>

    {{-- Kata sandi --}}
    <div class="card">
        <div class="card-header"><div class="card-title">{{ auth()->user()->password ? 'Ubah Kata Sandi' : 'Buat Kata Sandi' }}</div></div>
        <form wire:submit="simpanPassword" class="card-pad space-y-3">
            @if (session('password_ok'))
                <p class="text-sm text-brand-600">{{ session('password_ok') }}</p>
            @endif
            @if (auth()->user()->password)
                <div>
                    <label class="field-label">Kata sandi lama</label>
                    <input wire:model="password_lama" type="password" class="input @error('password_lama') input-error @enderror" autocomplete="current-password">
                    @error('password_lama') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                </div>
            @else
                <p class="text-xs text-neutral-400">Akun Google-mu belum punya kata sandi. Buat satu agar bisa login pakai NIP.</p>
            @endif
            <div>
                <label class="field-label">Kata sandi baru</label>
                <input wire:model="password" type="password" class="input @error('password') input-error @enderror" autocomplete="new-password">
                @error('password') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Ulangi kata sandi baru</label>
                <input wire:model="password_confirmation" type="password" class="input" autocomplete="new-password">
            </div>
            <div class="pt-1"><button type="submit" class="btn btn-primary btn-sm">Simpan Kata Sandi</button></div>
        </form>
    </div>

    {{-- Notifikasi perangkat --}}
    <div class="card card-pad">
        <div class="card-title mb-1">Notifikasi</div>
        <p class="text-sm text-neutral-500 mb-3">Aktifkan notifikasi push agar pengingat penting muncul di perangkat ini.</p>
        <x-push-subscribe />
    </div>
</div>
