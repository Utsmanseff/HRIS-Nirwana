<div class="auth">
    <div class="auth-brand">
        <div class="auth-grain"></div>
        <div class="flex items-center gap-3">
            <span class="grid place-items-center w-11 h-11 rounded-xl shadow-sm bg-white"><x-logo :size="26" /></span>
            <div>
                <div class="font-extrabold text-lg tracking-tight">Nirwana<span class="text-brand-200">HRIS</span></div>
                <div class="text-[11px] text-brand-200/70 font-semibold uppercase tracking-wider">RSU Nirwana</div>
            </div>
        </div>
        <div class="max-w-sm">
            <h2 class="text-[2rem] font-extrabold leading-tight tracking-tight mb-3">Satu langkah lagi.</h2>
            <p class="text-white/60 text-[15px] leading-relaxed">Hubungkan akun Google-mu ke data karyawan agar bisa mengakses layanan kepegawaian.</p>
        </div>
        <div class="text-xs text-white/40">© {{ date('Y') }} RSU Nirwana · Internal use only</div>
    </div>

    <div class="auth-form">
        <div class="w-full max-w-sm rise">
            <div class="mb-4">
                <h1 class="text-2xl font-extrabold tracking-tight mb-1">Hubungkan Data Karyawan</h1>
                <p class="text-neutral-500 text-sm">Cari data dirimu (NIK / NIP / Nama), lalu pilih untuk menghubungkan.</p>
            </div>

            <input wire:model.live.debounce.400ms="q" class="input @error('q') input-error @enderror" placeholder="Ketik NIK, NIP, atau nama…" autofocus>
            @error('q') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror

            <div class="mt-3 space-y-2">
                @forelse ($hasil as $k)
                    <button wire:click="klaim({{ $k->id }})" class="w-full text-left card card-pad flex items-center justify-between hover:border-brand-400">
                        <span>
                            <span class="font-semibold block">{{ $k->nama_lengkap }}</span>
                            <span class="font-mono text-xs text-neutral-500">{{ $k->nip }}</span>
                        </span>
                        <span class="badge badge-brand">Pilih</span>
                    </button>
                @empty
                    <p class="text-sm text-neutral-400 py-2">{{ mb_strlen(trim($q)) < 3 ? 'Ketik minimal 3 karakter.' : 'Tidak ada data cocok yang tersedia.' }}</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-5">@csrf
                <button class="btn btn-ghost btn-sm">Keluar</button>
            </form>
        </div>
    </div>
</div>
