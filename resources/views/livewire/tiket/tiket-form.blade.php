<div class="max-w-3xl mx-auto space-y-6 rise">
    <div>
        <a href="{{ route('tiket') }}" class="text-sm text-neutral-500 hover:underline">← Kembali</a>
        <h1 class="text-2xl font-extrabold tracking-tight mt-1">{{ $adalahTim ? 'Catat Tiket' : 'Buat Tiket Baru' }}</h1>
    </div>

    <form wire:submit="simpan" class="card card-pad space-y-4">
        {{-- Tim tujuan --}}
        <div>
            <label class="field-label">Tim tujuan</label>
            <select class="select" wire:model="tim" @if($inventarisId) disabled @endif>
                @foreach ($timOpsi as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
            </select>
            @if($inventarisId)<p class="text-xs text-neutral-400 mt-1">Tim mengikuti aset tertaut (otomatis).</p>@endif
            @error('tim')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror
        </div>

        @if ($adalahTim)
            {{-- Aset tertaut (opsional) --}}
            <div>
                <label class="field-label">Aset tertaut (opsional)</label>
                @if ($inventarisId)
                    <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-neutral-200">
                        <span class="text-sm font-semibold">{{ $asetLabel }}</span>
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="lepasAset">Lepas</button>
                    </div>
                @else
                    <input class="input" wire:model.live.debounce.300ms="cariAset" placeholder="Cari kode / nama aset…">
                    @if (count($asetOpsi))
                        <div class="mt-1 border border-neutral-200 rounded-lg divide-y divide-neutral-100">
                            @foreach ($asetOpsi as $a)
                                <button type="button" wire:click="pilihAset({{ $a['id'] }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-neutral-50">{{ $a['label'] }}</button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- Pelapor (opsional / internal) --}}
            <div>
                <label class="field-label">Pelapor (kosongkan = kerja internal)</label>
                @if ($pelaporId)
                    <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-neutral-200">
                        <span class="text-sm font-semibold">{{ $pelaporLabel }}</span>
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="lepasPelapor">Lepas</button>
                    </div>
                @else
                    <input class="input" wire:model.live.debounce.300ms="cariPelapor" placeholder="Cari nama / NIP…">
                    @if (count($pelaporOpsi))
                        <div class="mt-1 border border-neutral-200 rounded-lg divide-y divide-neutral-100">
                            @foreach ($pelaporOpsi as $p)
                                <button type="button" wire:click="pilihPelapor({{ $p['id'] }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-neutral-50">{{ $p['label'] }}</button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Jenis</label>
                    <select class="select" wire:model="jenis">
                        @foreach ($jenisOpsi as $j)<option value="{{ $j->value }}">{{ $j->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Waktu lapor</label>
                    <input type="datetime-local" class="input" wire:model="waktuLapor">
                </div>
            </div>
        @endif

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Prioritas</label>
                <select class="select" wire:model="prioritas">
                    @foreach ($prioritasOpsi as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="field-label">Judul masalah</label>
            <input class="input" wire:model="judul" placeholder="mis. Komputer tidak menyala">
            @error('judul')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label">Deskripsi</label>
            <textarea class="textarea" rows="3" wire:model="deskripsi" placeholder="Jelaskan masalahnya…"></textarea>
            @error('deskripsi')<p class="text-xs text-danger-600 mt-1">{{ $message }}</p>@enderror
        </div>

        @if ($adalahTim)
            {{-- Status lanjut (rekam kerja lisan / langsung selesai) --}}
            <div>
                <label class="field-label">Status awal</label>
                <select class="select" wire:model.live="statusLanjut">
                    <option value="baru">Baru (masuk antrian)</option>
                    <option value="diproses">Langsung diproses</option>
                    <option value="selesai">Langsung selesai</option>
                </select>
            </div>
            @if ($statusLanjut === 'selesai')
                <div>
                    <label class="field-label">Catatan penyelesaian</label>
                    <textarea class="textarea" rows="2" wire:model="catatanPenyelesaian" placeholder="Apa yang dikerjakan…"></textarea>
                </div>
            @endif
        @endif

        <div class="flex gap-2.5 pt-1">
            <a href="{{ route('tiket') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary flex-1">{{ $adalahTim ? 'Simpan Tiket' : 'Kirim Tiket' }}</button>
        </div>
    </form>
</div>
