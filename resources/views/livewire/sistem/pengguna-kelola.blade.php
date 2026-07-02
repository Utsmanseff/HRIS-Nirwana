<div class="space-y-4 rise">
    <div>
        <h1 class="text-lg font-extrabold tracking-tight">Pengguna &amp; Role</h1>
        <p class="text-sm text-neutral-500">Kelola akun login, role, dan hak akses.</p>
    </div>

    <div class="flex gap-1 border-b border-neutral-200">
        <button wire:click="$set('tab', 'pengguna')" class="tab-btn {{ $tab === 'pengguna' ? 'on' : '' }}">Pengguna</button>
        <button wire:click="$set('tab', 'role')" class="tab-btn {{ $tab === 'role' ? 'on' : '' }}">Role &amp; Hak Akses</button>
    </div>

    @if ($tab === 'pengguna')
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[220px]">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"><x-icon name="search" :size="15" /></span>
                <input wire:model.live.debounce.300ms="q" class="input" style="padding-left:2.35rem"
                       placeholder="Cari nama, email, atau NIP…">
            </div>
            <select wire:model.live="filterRole" class="input" style="max-width:180px">
                <option value="">Semua role</option>
                @foreach ($semuaRole as $r)
                    <option value="{{ $r->value }}">{{ $r->value }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStatus" class="input" style="max-width:150px">
                <option value="">Semua status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>

        <div class="card overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead>
                    <tr class="text-left text-neutral-400 border-b border-neutral-200">
                        <th class="px-4 py-2.5 font-semibold">Akun</th>
                        <th class="px-4 py-2.5 font-semibold">Karyawan Tertaut</th>
                        <th class="px-4 py-2.5 font-semibold">Role</th>
                        <th class="px-4 py-2.5 font-semibold">Google</th>
                        <th class="px-4 py-2.5 font-semibold">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr class="border-b border-neutral-100 last:border-0 align-top">
                            <td class="px-4 py-2.5">
                                <div class="font-semibold">{{ $u->name }}</div>
                                <div class="text-xs text-neutral-400">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-2.5">
                                @if ($u->karyawan)
                                    <div class="font-medium">{{ $u->karyawan->nama_lengkap }}</div>
                                    <div class="font-mono text-xs text-neutral-400">{{ $u->karyawan->nip }}</div>
                                @else
                                    <span class="badge badge-warning">Belum tertaut</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($u->roles as $role)
                                        <span class="badge badge-neutral">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-xs text-neutral-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-xs">{{ $u->google_id ? 'Tertaut' : '—' }}</td>
                            <td class="px-4 py-2.5">
                                @if ($u->akunAktif())
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                {{-- Tombol Kelola diisi Task 5 --}}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-neutral-400">Tidak ada pengguna cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('livewire.sdm.partials.pager') }}
    @endif

    @if ($tab === 'role')
        <p class="text-sm text-neutral-400">Matriks RBAC menyusul di task berikutnya.</p>
    @endif
</div>
