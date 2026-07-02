<div class="space-y-4 rise">
    <div class="flex items-center justify-between">
        <div><h1 class="text-lg font-extrabold tracking-tight">Karyawan</h1>
            <p class="text-sm text-neutral-500">Data induk karyawan RSU Nirwana.</p></div>
    </div>

    <div class="card">
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
