<div class="max-w-5xl mx-auto space-y-6 rise">
    <div>
        <a href="{{ route('tiket') }}" class="text-sm text-neutral-500 hover:underline">← Kembali</a>
        <h1 class="text-2xl font-extrabold tracking-tight mt-1">{{ $adalahTim ? 'Catat Tiket' : 'Buat Tiket Baru' }}</h1>
        <p class="text-sm text-neutral-500 mt-0.5">{{ $adalahTim ? 'Catat laporan a.n. pelapor atau kerja internal tim.' : 'Laporkan kendala ke tim teknis. Kami akan menindaklanjuti.' }}</p>
    </div>

    <form wire:submit="simpan" class="grid lg:grid-cols-3 gap-6 items-start">
        {{-- Kolom form --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Tim tujuan (chip) --}}
            <div class="card card-pad">
                <label class="field-label">Tim tujuan</label>
                @if ($inventarisId)
                    <div class="flex items-center gap-2 mt-1 text-sm">
                        <span class="badge badge-neutral">Tim {{ \App\Enums\TimTeknis::from($tim)->label() }}</span>
                        <span class="text-xs text-neutral-400">otomatis mengikuti aset tertaut</span>
                    </div>
                @else
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        @foreach ($timOpsi as $t)
                            <button type="button" wire:click="$set('tim', '{{ $t->value }}')"
                                class="rounded-xl border-2 px-3 py-3 text-sm font-semibold transition text-center
                                    {{ $tim === $t->value ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300' }}">
                                {{ $t->label() }}
                            </button>
                        @endforeach
                    </div>
                @endif
                @error('tim')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Jenis (chip, tim saja) --}}
            @if ($adalahTim)
                <div class="card card-pad">
                    <label class="field-label">Jenis tiket</label>
                    <div class="grid sm:grid-cols-2 gap-2.5 mt-1">
                        @foreach ($jenisOpsi as $j)
                            <button type="button" wire:click="$set('jenis', '{{ $j->value }}')"
                                class="flex items-start gap-3 rounded-xl border-2 p-3 text-left transition
                                    {{ $jenis === $j->value ? 'border-brand-500 bg-brand-50' : 'border-neutral-200 hover:border-neutral-300' }}">
                                <span class="w-9 h-9 rounded-lg grid place-items-center shrink-0 {{ $jenis === $j->value ? 'bg-brand-100 text-brand-700' : 'bg-neutral-100 text-neutral-500' }}">
                                    <x-icon :name="$j === \App\Enums\JenisTiket::Pemeliharaan ? 'clock' : 'sliders'" :size="18" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold">{{ $j->label() }}</span>
                                    <span class="block text-xs text-neutral-400">{{ $j === \App\Enums\JenisTiket::Pemeliharaan ? 'Servis/kalibrasi terjadwal' : 'Kerusakan / gangguan' }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Aset & Pelapor (tim) --}}
            @if ($adalahTim)
                <div class="card card-pad space-y-4">
                    {{-- Aset --}}
                    <div>
                        <label class="field-label">Aset tertaut <span class="text-neutral-400 font-normal">(opsional)</span></label>
                        @if ($inventarisId)
                            <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-brand-200 bg-brand-50/50">
                                <span class="flex items-center gap-2 text-sm font-semibold"><x-icon name="box" :size="16" class="text-brand-600" />{{ $asetLabel }}</span>
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="lepasAset">Lepas</button>
                            </div>
                        @else
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"><x-icon name="search" :size="16" /></span>
                                <input class="input pl-9" wire:model.live.debounce.300ms="cariAset" placeholder="Cari kode / nama aset…">
                            </div>
                            @if (count($asetOpsi))
                                <div class="mt-1 border border-neutral-200 rounded-lg divide-y divide-neutral-100 overflow-hidden">
                                    @foreach ($asetOpsi as $a)
                                        <button type="button" wire:click="pilihAset({{ $a['id'] }})" class="flex items-center gap-2 w-full text-left px-3 py-2 text-sm hover:bg-neutral-50"><x-icon name="box" :size="15" class="text-neutral-400" />{{ $a['label'] }}</button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Pelapor --}}
                    <div>
                        <label class="field-label">Pelapor <span class="text-neutral-400 font-normal">(kosongkan = kerja internal)</span></label>
                        @if ($pelaporId)
                            <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-neutral-200">
                                <span class="flex items-center gap-2 text-sm font-semibold"><x-icon name="user" :size="16" class="text-neutral-400" />{{ $pelaporLabel }}</span>
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="lepasPelapor">Lepas</button>
                            </div>
                        @else
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"><x-icon name="search" :size="16" /></span>
                                <input class="input pl-9" wire:model.live.debounce.300ms="cariPelapor" placeholder="Cari nama / NIP…">
                            </div>
                            @if (count($pelaporOpsi))
                                <div class="mt-1 border border-neutral-200 rounded-lg divide-y divide-neutral-100 overflow-hidden">
                                    @foreach ($pelaporOpsi as $p)
                                        <button type="button" wire:click="pilihPelapor({{ $p['id'] }})" class="flex items-center gap-2 w-full text-left px-3 py-2 text-sm hover:bg-neutral-50"><x-icon name="user" :size="15" class="text-neutral-400" />{{ $p['label'] }}</button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>

                    <div>
                        <label class="field-label">Waktu lapor</label>
                        <input type="datetime-local" class="input" wire:model="waktuLapor">
                    </div>
                </div>
            @endif

            {{-- Isi tiket --}}
            <div class="card card-pad space-y-4">
                <div>
                    <label class="field-label">Judul masalah</label>
                    <input class="input" wire:model="judul" placeholder="mis. Komputer tidak menyala">
                    @error('judul')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">Deskripsi</label>
                    <textarea class="textarea" rows="4" wire:model="deskripsi" placeholder="Jelaskan gejala, lokasi, dan kapan mulai terjadi…"></textarea>
                    @error('deskripsi')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Prioritas (chip warna) --}}
                <div>
                    <label class="field-label">Prioritas</label>
                    <div class="grid grid-cols-4 gap-2 mt-1">
                        @foreach ($prioritasOpsi as $p)
                            <button type="button" wire:click="$set('prioritas', '{{ $p->value }}')"
                                class="rounded-lg border-2 px-2 py-2 text-xs font-bold transition
                                    {{ $prioritas === $p->value ? 'border-brand-500 bg-brand-50' : 'border-neutral-200 text-neutral-500 hover:border-neutral-300' }}">
                                <span class="badge {{ $p->badge() }} pointer-events-none">{{ $p->label() }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Status awal (tim) --}}
            @if ($adalahTim)
                <div class="card card-pad space-y-3">
                    <label class="field-label">Status awal</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai'] as $val => $lbl)
                            <button type="button" wire:click="$set('statusLanjut', '{{ $val }}')"
                                class="rounded-lg border-2 px-3 py-2.5 text-sm font-semibold transition
                                    {{ $statusLanjut === $val ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                    <p class="text-xs text-neutral-400">
                        {{ $statusLanjut === 'selesai' ? 'Tiket langsung selesai (rekam kerja lisan). Waktu respon & selesai terisi sekarang.' : ($statusLanjut === 'diproses' ? 'Langsung ambil & proses tiket.' : 'Masuk antrian tim.') }}
                    </p>
                    @if ($statusLanjut === 'selesai')
                        <div>
                            <label class="field-label">Catatan penyelesaian</label>
                            <textarea class="textarea" rows="2" wire:model="catatanPenyelesaian" placeholder="Apa yang dikerjakan…"></textarea>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Aside: ringkasan --}}
        <aside class="card card-pad lg:sticky lg:top-6 space-y-4">
            <div class="text-[13px] font-bold text-neutral-700">Ringkasan</div>

            <div class="flex items-center gap-2 flex-wrap">
                <span class="badge badge-neutral">Tim {{ \App\Enums\TimTeknis::from($tim)->label() }}</span>
                <span class="badge {{ \App\Enums\PrioritasTiket::from($prioritas)->badge() }}">{{ \App\Enums\PrioritasTiket::from($prioritas)->label() }}</span>
                @if ($adalahTim)<span class="badge badge-info">{{ \App\Enums\JenisTiket::from($jenis)->label() }}</span>@endif
            </div>

            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between gap-2"><dt class="text-neutral-400">Judul</dt><dd class="font-semibold text-right truncate max-w-[60%]">{{ $judul ?: '—' }}</dd></div>
                @if ($adalahTim)
                    <div class="flex justify-between gap-2"><dt class="text-neutral-400">Aset</dt><dd class="font-semibold text-right truncate max-w-[60%]">{{ $asetLabel ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-neutral-400">Pelapor</dt><dd class="font-semibold text-right truncate max-w-[60%]">{{ $pelaporLabel ?: 'Internal' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-neutral-400">Status awal</dt><dd class="font-semibold text-right capitalize">{{ $statusLanjut }}</dd></div>
                @endif
            </dl>

            <div class="pt-3 border-t border-neutral-100 flex flex-col gap-2">
                <button type="submit" class="btn btn-primary w-full">{{ $adalahTim ? 'Simpan Tiket' : 'Kirim Tiket' }}</button>
                <a href="{{ route('tiket') }}" class="btn btn-secondary w-full">Batal</a>
            </div>
        </aside>
    </form>
</div>
