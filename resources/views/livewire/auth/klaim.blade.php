<div class="card card-pad">
    <div class="flex items-center gap-3 mb-4">
        <span class="grid place-items-center w-11 h-11 rounded-xl bg-neutral-100"><x-logo :size="26" /></span>
        <div>
            <div class="card-title">Hubungkan Data Karyawan</div>
            <p class="text-sm text-neutral-500">Cari data dirimu (NIK / NIP / Nama), lalu pilih untuk menghubungkan.</p>
        </div>
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
