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
                                <button wire:click="bukaKelola({{ $u->id }})" class="btn btn-ghost btn-sm">Kelola</button>
                            </td>
                        </tr>
                        @if ($kelolaId === $u->id)
                            <tr class="border-b border-neutral-100 bg-neutral-50">
                                <td colspan="6" class="px-4 py-4">
                                    @error('kelola') <p class="text-sm font-semibold mb-3" style="color:var(--danger-500)">{{ $message }}</p> @enderror
                                    @if (session('pesan')) <p class="text-sm font-semibold mb-3" style="color:var(--brand-600)">{{ session('pesan') }}</p> @endif

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div>
                                            <div class="field-label mb-2">Role (multi-role — hak = gabungan)</div>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                @foreach ($semuaRole as $r)
                                                    <label class="flex items-center gap-2 text-sm">
                                                        <input type="checkbox" wire:model="rolePilihan" value="{{ $r->value }}" class="w-4 h-4 accent-brand-500">
                                                        <span>{{ $r->value }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <button wire:click="simpanRole" class="btn btn-primary btn-sm">Simpan Role</button>
                                                <button wire:click="tutupKelola" class="btn btn-ghost btn-sm">Tutup</button>
                                            </div>
                                        </div>
                                        <div class="space-y-3">
                                            <div>
                                                <button wire:click="resetSandi"
                                                        wire:confirm="Reset sandi akun {{ $u->name }}? Sandi lama tidak berlaku lagi."
                                                        class="btn btn-secondary btn-sm w-full">Reset Sandi</button>
                                                @if ($sandiSementara)
                                                    <div class="mt-2 p-3 rounded-lg border border-neutral-200 bg-neutral-50 text-sm">
                                                        Sandi sementara (catat sekarang, hanya tampil sekali):
                                                        <div class="font-mono font-bold text-base mt-1">{{ $sandiSementara }}</div>
                                                        <div class="text-xs text-neutral-400 mt-1">Sampaikan ke karyawan; sarankan ganti di halaman Profil.</div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                @if ($u->akunAktif())
                                                    <button wire:click="toggleAktif"
                                                            wire:confirm="Nonaktifkan akun {{ $u->name }}? Ia tidak akan bisa login dan sesi berjalannya diputus."
                                                            class="btn btn-secondary btn-sm w-full" style="color:var(--danger-500)">Nonaktifkan Akun</button>
                                                @else
                                                    <button wire:click="toggleAktif" class="btn btn-secondary btn-sm w-full">Aktifkan Lagi</button>
                                                @endif
                                            </div>
                                            @if ($u->karyawan)
                                                <div>
                                                    <button wire:click="unlink"
                                                            wire:confirm="Putuskan tautan akun {{ $u->name }} dari karyawan {{ $u->karyawan->nama_lengkap }} ({{ $u->karyawan->nip }})? Semua role dicabut dan data karyawan bisa diklaim ulang."
                                                            class="btn btn-secondary btn-sm w-full" style="color:var(--danger-500)">Putuskan Tautan Karyawan</button>
                                                    <p class="text-xs text-neutral-400 mt-1">Untuk kasus salah klaim / penyalahgunaan identitas.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-neutral-400">Tidak ada pengguna cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('livewire.sdm.partials.pager') }}
    @endif

    @if ($tab === 'role')
        {{-- Kartu role --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($daftarRole as $role)
                <div class="card card-pad">
                    <div class="flex items-center justify-between mb-2">
                        <span class="badge {{ $role->name === 'Admin Sistem' ? 'badge-danger' : ($role->name === 'Direktur' ? 'badge-warning' : 'badge-brand') }}">{{ $role->name }}</span>
                        <span class="text-xs text-neutral-400 font-mono">{{ $role->users_count }} user</span>
                    </div>
                    <p class="text-[13px] text-neutral-500 leading-relaxed">{{ $deskripsiRole[$role->name] ?? '' }}</p>
                </div>
            @endforeach
            <div class="card card-pad sm:col-span-2 lg:col-span-3">
                <p class="text-[13px] text-neutral-500 leading-relaxed">
                    <b>Atasan / Koordinator / Kabid bukan role.</b> Kemampuan approve cuti &amp; kelola jadwal
                    diturunkan otomatis dari struktur (punya bawahan via <code>atasan_id</code>, level jabatan) —
                    tidak perlu dan tidak bisa di-assign di sini.
                </p>
            </div>
        </div>

        {{-- Matriks --}}
        <div class="card overflow-x-auto">
            <div class="card-header">
                <div>
                    <div class="card-title">Matriks Hak Akses</div>
                    <div class="text-xs text-neutral-400 mt-0.5">Multi-role: hak = gabungan semua role. Data langsung dari database.</div>
                </div>
            </div>
            <table class="w-full text-sm min-w-[860px]">
                <thead>
                    <tr class="text-left text-neutral-400 border-b border-neutral-200">
                        <th class="px-4 py-2.5 font-semibold">Kemampuan</th>
                        @foreach ($daftarRole as $role)
                            <th class="px-3 py-2.5 font-semibold text-center">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarPermission as $p)
                        <tr class="border-b border-neutral-100 last:border-0">
                            <td class="px-4 py-2.5 font-medium">{{ $labelPermission[$p->value] ?? $p->value }}</td>
                            @foreach ($daftarRole as $role)
                                <td class="px-3 py-2.5 text-center">
                                    @if ($role->name === 'Admin Sistem' || $role->permissions->contains('name', $p->value))
                                        <span class="font-bold" style="color:var(--brand-600)">✓</span>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-2.5 text-xs text-neutral-400 border-t border-neutral-100">Kolom Admin Sistem ✓ semua karena bypass RBAC (Gate::before).</div>
        </div>
    @endif
</div>
