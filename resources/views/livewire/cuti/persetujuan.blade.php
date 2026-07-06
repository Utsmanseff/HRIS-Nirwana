<div class="mx-auto max-w-6xl">
    <h1 class="text-xl font-bold mb-4">Persetujuan Cuti</h1>

    <div class="card">
        <div class="px-4 flex gap-1 border-b border-neutral-100">
            <button wire:click="$set('tab','perlu-aksi')"
                class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab==='perlu-aksi' ? 'text-brand-600 border-brand-500' : 'text-neutral-500 border-transparent' }}">
                Perlu Aksi Saya
                @if($perluAksi->isNotEmpty())
                    <span class="ml-1 inline-grid place-items-center w-5 h-5 rounded-full bg-warning-500 text-white text-[11px]">{{ $perluAksi->count() }}</span>
                @endif
            </button>
        </div>

        @if($tab==='perlu-aksi')
            @if($perluAksi->isEmpty())
                <div class="card-pad text-sm text-neutral-500">Tak ada pengajuan yang menunggu aksi Anda.</div>
            @else
                <table class="table rtable">
                    <thead><tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Hari</th><th>Tahap</th><th></th></tr></thead>
                    <tbody>
                        @foreach($perluAksi as $p)
                            <tr wire:key="pa-{{ $p->id }}">
                                <td data-primary>
                                    <div class="font-semibold">{{ $p->karyawan->nama_lengkap }}</div>
                                    <div class="text-xs text-neutral-400">{{ $p->karyawan->jabatan?->nama }}</div>
                                </td>
                                <td data-label="Jenis">{{ $p->jenisCuti->nama }}</td>
                                <td data-label="Tanggal" class="tnum text-neutral-600">{{ $p->tanggal_mulai->format('d M') }}–{{ $p->tanggal_selesai->format('d M') }}</td>
                                <td data-label="Hari" class="tnum font-semibold">{{ $p->jumlah_hari }}</td>
                                <td data-label="Tahap">{{ ucfirst($p->tahapAktif()?->peran?->value ?? '') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('cuti.detail', $p) }}" class="btn btn-secondary btn-sm">Tinjau</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    </div>
</div>
