<div class="max-w-3xl mx-auto p-4 sm:p-6 space-y-4 rise">
    <div>
        <h1 class="text-lg font-extrabold tracking-tight">Pengganti Jadwal</h1>
        <p class="text-sm text-neutral-500">Cuti berjalan dan jadwal kosong di unit Anda. Ajukan diri jadi pengganti, atau (koordinator) alihkan cakupan.</p>
    </div>

    @if (session('cuti_ok'))
        <div class="rounded-md px-3 py-2 text-sm" style="background:var(--brand-50);color:var(--brand-700)">{{ session('cuti_ok') }}</div>
    @endif

    @forelse ($kartu as $k)
        @php $lowongan = $k['tipe'] === \App\Enums\TipePengganti::Lowongan; @endphp
        <div wire:key="{{ $k['kunci'] }}" class="card card-pad space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-semibold text-sm">{{ $k['judul'] }}</div>
                    <div class="text-xs text-neutral-500 tnum">{{ $k['sub'] }}</div>
                </div>
                @if ($lowongan)
                    <span class="badge badge-warning">Jadwal Kosong</span>
                @else
                    <span class="badge badge-neutral">Cuti</span>
                @endif
            </div>

            <div class="space-y-1">
                @forelse ($k['rencana']->where('status', \App\Enums\StatusPengganti::Aktif) as $pg)
                    <div wire:key="pg-{{ $pg->id }}" class="text-sm flex justify-between">
                        <span>{{ $pg->karyawan->nama_lengkap }}</span>
                        <span class="text-xs text-neutral-500 tnum">
                            {{ $pg->tanggal_mulai->format('d M') }} s/d
                            {{ $pg->tanggal_selesai?->format('d M') ?? 'seterusnya' }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-neutral-500">Belum ada pengganti.</p>
                @endforelse
            </div>

            @if ($this->sayaKoordinatorUnit($k['digantikan']))
                @foreach ($k['rencana']->where('status', \App\Enums\StatusPengganti::Usulan) as $us)
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
                @if ($this->sayaRekanUnit($k['digantikan']))
                    <button wire:click="mulaiAjukan('{{ $k['kunci'] }}')" class="btn btn-ghost btn-sm">Ajukan diri</button>
                @endif
                @if ($this->sayaKoordinatorUnit($k['digantikan']))
                    <button wire:click="mulaiAlih('{{ $k['kunci'] }}')" class="btn btn-ghost btn-sm">Alihkan</button>
                    @if ($lowongan)
                        <button wire:click="mulaiSelesai({{ $k['digantikan']->id }})" class="btn btn-ghost btn-sm">Selesai</button>
                    @endif
                @endif
            </div>

            @if ($ajukanId === $k['kunci'])
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

            @if ($alihId === $k['kunci'])
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

            @if ($lowongan && $selesaiKaryawanId === $k['digantikan']->id)
                <div class="rounded-md border border-neutral-200 p-3 space-y-2">
                    <p class="text-sm">
                        Jadwal {{ $k['judul'] }} sejak {{ now()->translatedFormat('d M Y') }} dihapus,
                        salinan penggantinya ikut dilepas. Jadwal yang sudah lewat tak disentuh.
                    </p>
                    @error('selesai') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                    <div class="flex gap-2">
                        <button wire:click="konfirmasiSelesai" class="btn btn-primary btn-sm">Ya, tutup lowongan</button>
                        <button wire:click="batalSelesai" class="btn btn-ghost btn-sm">Batal</button>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="card card-pad text-sm text-neutral-500">Tak ada cuti berjalan atau jadwal kosong di unit Anda.</div>
    @endforelse
</div>
