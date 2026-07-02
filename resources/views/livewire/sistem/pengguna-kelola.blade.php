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
        <p class="text-sm text-neutral-400">Daftar pengguna menyusul di task berikutnya.</p>
    @endif

    @if ($tab === 'role')
        <p class="text-sm text-neutral-400">Matriks RBAC menyusul di task berikutnya.</p>
    @endif
</div>
