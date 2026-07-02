<div class="space-y-4 rise">
    <div class="flex items-center justify-between">
        <div><h1 class="text-lg font-extrabold tracking-tight">Karyawan</h1>
            <p class="text-sm text-neutral-500">Data induk karyawan RSU Nirwana.</p></div>
    </div>

    <div class="card">
        <div class="p-4 flex flex-wrap items-center gap-2.5 border-b border-neutral-100">
            <div class="relative flex-1 min-w-[220px]">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"><svg width="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4" stroke-linecap="round"/></svg></span>
                <input wire:model.live.debounce.300ms="cari" class="input pl-9" placeholder="Cari nama atau NIP…">
            </div>
            <select wire:model.live="unitId" class="select w-auto">
                <option value="">Semua Unit</option>
                @foreach ($unitOptions as $u)
                    <option value="{{ $u->id }}">{{ $u->nama }}</option>
                @endforeach
            </select>
            <select wire:model.live="level" class="select w-auto">
                <option value="">Semua Level</option>
                @foreach ($levelOptions as $lv)
                    <option value="{{ $lv->value }}">L{{ $lv->value }} · {{ ucfirst($lv->name) }}</option>
                @endforeach
            </select>
            <select wire:model.live="kontrakJenis" class="select w-auto">
                <option value="">Semua Kontrak</option>
                @foreach ($kontrakOptions as $jk)
                    <option value="{{ $jk->value }}">{{ $jk->label() }}</option>
                @endforeach
            </select>
            <div class="inline-flex rounded-md bg-neutral-100 p-0.5 gap-0.5">
                @foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'semua' => 'Semua'] as $nilai => $labelStatus)
                    <button wire:click="$set('status', '{{ $nilai }}')"
                        class="px-3 py-1.5 rounded-[7px] text-xs font-semibold {{ $status === $nilai ? 'bg-white text-brand-700 shadow-sm' : 'text-neutral-500 hover:text-neutral-800' }}">
                        {{ $labelStatus }}
                    </button>
                @endforeach
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Karyawan</th><th>NIP</th><th>Unit / Jabatan</th><th>Atasan</th><th>Kontrak</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($karyawan as $k)
                    @php [$badgeTeks, $badgeKelas] = $this->badgeKontrak($k); @endphp
                    <tr class="table-row-link">
                        <td>
                            <div class="font-semibold">{{ $k->nama_lengkap }}</div>
                            <div class="text-xs text-neutral-400">{{ $k->jabatan->nama }} · L{{ $k->jabatan->level->value }}</div>
                        </td>
                        <td class="font-mono text-[13px] tnum text-neutral-500">{{ $k->nip }}</td>
                        <td>
                            <div class="text-[13px]">{{ $k->orgUnit->nama }}</div>
                            <div class="text-xs text-neutral-400">{{ $k->orgUnit->parent?->nama }}</div>
                        </td>
                        <td class="text-[13px] text-neutral-600">{{ $k->atasan?->nama_lengkap ?? '—' }}</td>
                        <td><span class="badge {{ $badgeKelas }}">{{ $badgeTeks }}</span></td>
                        <td>
                            @if ($k->status->value === 'aktif')
                                <span class="badge badge-success"><span class="dot"></span>Aktif</span>
                            @else
                                <span class="badge badge-neutral">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-neutral-400 py-8">Tidak ada karyawan.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $karyawan->links('livewire.sdm.partials.pager') }}
    </div>
</div>
