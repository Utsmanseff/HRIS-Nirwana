<div class="auth">
    <div class="auth-brand">
        <div class="auth-grain"></div>
        <div class="auth-id flex items-center gap-3">
            <span class="auth-mark grid place-items-center w-11 h-11 rounded-xl shadow-sm bg-white"><x-logo :size="26" /></span>
            <div>
                <div class="auth-word font-extrabold text-lg tracking-tight">Nirwana<span class="text-brand-200">HRIS</span></div>
                <div class="auth-sub text-[11px] text-brand-200/70 font-semibold uppercase tracking-wider">RSU Nirwana</div>
                <div class="auth-tagline">Sistem Kepegawaian RSU Nirwana</div>
            </div>
        </div>
        <div class="auth-hero max-w-sm">
            <h2 class="text-[2rem] font-extrabold leading-tight tracking-tight mb-3">Satu sistem untuk seluruh kepegawaian.</h2>
            <p class="text-white/60 text-[15px] leading-relaxed">Data karyawan, kontrak, cuti, absensi, hingga aset — terpusat, rapi, tanpa input ganda.</p>
        </div>
        <div class="auth-foot text-xs text-white/40">© {{ date('Y') }} RSU Nirwana · Internal use only</div>
    </div>

    <div class="auth-form">
        <div class="w-full max-w-sm rise">
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold tracking-tight mb-1">Masuk ke akun Anda</h1>
                <p class="text-neutral-500 text-sm">Gunakan akun Google kantor, atau NIP &amp; kata sandi.</p>
            </div>

            {{-- Aksi utama: Google --}}
            <a href="{{ route('auth.google') }}" class="btn btn-primary btn-lg w-full gap-2">
                <x-icon-google :size="18" /> Masuk dengan Google
            </a>

            <div class="flex items-center gap-3 my-5 text-neutral-400">
                <div class="flex-1 h-px bg-neutral-200"></div><span class="text-xs">atau pakai NIP</span><div class="flex-1 h-px bg-neutral-200"></div>
            </div>

            {{-- Alternatif: NIP + kata sandi --}}
            <form wire:submit="login" class="space-y-4">
                <div>
                    <label class="field-label">NIP</label>
                    <input wire:model="nip" class="input font-mono @error('nip') input-error @enderror" placeholder="1990.04.21.001" autocomplete="username">
                    @error('nip') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Kata sandi</label>
                    <input wire:model="password" type="password" class="input" placeholder="••••••••" autocomplete="current-password">
                </div>
                <label class="flex items-center gap-2 text-sm text-neutral-600">
                    <input wire:model="remember" type="checkbox" class="w-4 h-4 accent-brand-500"> <span>Ingat saya</span>
                </label>
                <button type="submit" class="btn btn-secondary w-full">Masuk dengan NIP</button>
            </form>

            <p class="mt-6 text-xs text-neutral-400 leading-relaxed">
                Belum terhubung? Masuk dengan Google lalu hubungkan data karyawanmu.
                Aplikasi bisa dipasang ke layar utama (PWA).
            </p>
        </div>
    </div>
</div>
