<div class="max-w-3xl mx-auto p-4 sm:p-6 space-y-4 rise">
    <div>
        <h1 class="text-lg font-extrabold tracking-tight">Pengganti Cuti</h1>
        <p class="text-sm text-neutral-500">Cuti berjalan di unit Anda. Ajukan diri jadi pengganti, atau (koordinator) alihkan cakupan.</p>
    </div>

    @if (session('cuti_ok'))
        <div class="rounded-md px-3 py-2 text-sm" style="background:var(--brand-50);color:var(--brand-700)">{{ session('cuti_ok') }}</div>
    @endif

    @forelse ($daftar as $cuti)
        <div wire:key="cuti-{{ $cuti->id }}" class="card card-pad space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-semibold text-sm">{{ $cuti->karyawan->nama_lengkap }}</div>
                    <div class="text-xs text-neutral-500 tnum">
                        {{ $cuti->karyawan->orgUnit?->nama }} ·
                        {{ $cuti->tanggal_mulai->format('d M') }} s/d {{ $cuti->tanggal_selesai->format('d M Y') }}
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                @forelse ($cuti->pengganti->where('status', \App\Enums\StatusPengganti::Aktif) as $pg)
                    <div wire:key="pg-{{ $pg->id }}" class="text-sm flex justify-between">
                        <span>{{ $pg->karyawan->nama_lengkap }}</span>
                        <span class="text-xs text-neutral-500 tnum">
                            {{ $pg->tanggal_mulai->format('d M') }} s/d {{ $pg->tanggal_selesai->format('d M') }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">Belum ada pengganti.</p>
                @endforelse
            </div>

            @if ($this->sayaKoordinator($cuti))
                @foreach ($cuti->pengganti->where('status', \App\Enums\StatusPengganti::Usulan) as $us)
                    <div wire:key="us-{{ $us->id }}" class="rounded-md border border-neutral-200 px-3 py-2 flex items-center justify-between gap-2">
                        <span class="text-sm">
                            {{ $us->karyawan->nama_lengkap }} mengajukan diri mulai {{ $us->tanggal_mulai->format('d M') }}
                        </span>
                        <span class="flex gap-1">
                            <button wire:click="acc({{ $us->id }})" class="btn btn-primary btn-sm">Acc</button>
                            <button wire:click="tolak({{ $us->id }})" class="btn btn-ghost btn-sm">Tolak</button>
                        </span>
                    </div>
                @endforeach
            @endif

            <div class="flex gap-2">
                @if ($this->sayaRekan($cuti))
                    <button wire:click="mulaiAjukan({{ $cuti->id }})" class="btn btn-ghost btn-sm">Ajukan diri</button>
                @endif
                @if ($this->sayaKoordinator($cuti))
                    <button wire:click="mulaiAlih({{ $cuti->id }})" class="btn btn-ghost btn-sm">Alihkan</button>
                @endif
            </div>

            @if ($ajukanId === $cuti->id)
                <div class="rounded-md border border-neutral-200 p-3 space-y-2">
                    <label class="field-label">Mulai tanggal</label>
                    <input type="date" wire:model="tanggalAksi" class="input">
                    @error('tanggalAksi') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    <div class="flex gap-2">
                        <button wire:click="kirimAjukanDiri" class="btn btn-primary btn-sm">Kirim usulan</button>
                        <button wire:click="batal" class="btn btn-ghost btn-sm">Batal</button>
                    </div>
                </div>
            @endif

            @if ($alihId === $cuti->id)
                <div class="rounded-md border border-neutral-200 p-3 space-y-2">
                    <label class="field-label">Alihkan mulai tanggal</label>
                    <input type="date" wire:model="tanggalAksi" class="input">
                    @error('tanggalAksi') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    <input type="text" wire:model.live.debounce.400ms="cariPengganti" class="input" placeholder="Cari pengganti baru…">
                    @foreach ($hasilCariPengganti as $kandidat)
                        <button wire:key="ak-{{ $kandidat->id }}" wire:click="pilihAlih({{ $kandidat->id }})"
                            class="w-full text-left px-3 py-2 rounded-md hover:bg-neutral-100 text-sm">
                            {{ $kandidat->nama_lengkap }} <span class="text-xs text-neutral-400">· {{ $kandidat->nip }}</span>
                        </button>
                    @endforeach
                    <button wire:click="batal" class="btn btn-ghost btn-sm">Batal</button>
                </div>
            @endif
        </div>
    @empty
        <div class="card card-pad text-sm text-neutral-500">Tak ada cuti berjalan di unit Anda.</div>
    @endforelse
</div>
