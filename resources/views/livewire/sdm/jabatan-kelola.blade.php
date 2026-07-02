<div class="space-y-4 rise">
    <div class="flex items-center justify-between">
        <div><h1 class="text-lg font-extrabold tracking-tight">Jabatan &amp; Level</h1>
            <p class="text-sm text-neutral-500">Kelola daftar jabatan dan levelnya.</p></div>
    </div>

    <div class="card">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-neutral-400 border-b border-neutral-200">
                    <th class="px-4 py-2.5 font-semibold">Jabatan</th>
                    <th class="px-4 py-2.5 font-semibold">Level</th>
                    <th class="px-4 py-2.5 font-semibold text-right">Karyawan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jabatan as $j)
                    <tr class="border-b border-neutral-100 last:border-0">
                        <td class="px-4 py-2.5 font-semibold">{{ $j->nama }}</td>
                        <td class="px-4 py-2.5"><span class="badge badge-neutral">L{{ $j->level->value }} · {{ ucfirst($j->level->name) }}</span></td>
                        <td class="px-4 py-2.5 text-right font-mono">{{ $j->karyawan_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
