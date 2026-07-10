<?php

namespace App\Providers;

use App\Enums\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin Sistem bypass penuh.
        Gate::before(fn ($user, $ability) => $user->hasRole(Role::AdminSistem->value) ? true : null);

        // Kemampuan struktural (derived dari struktur org + jabatan.level), bukan role.
        Gate::define('ajukan-cuti', fn ($user) => $user->karyawan !== null);
        Gate::define('approve-cuti', fn ($user) => (bool) ($user->karyawan?->punyaBawahan() || $user->hasRole(Role::Hrd->value)));
        Gate::define('usul-disiplin', fn ($user) => (bool) $user->karyawan?->punyaBawahan());
        Gate::define('kelola-cuti', fn ($user) => $user->hasRole(Role::Hrd->value));
        Gate::define('kelola-disiplin', fn ($user) => $user->hasRole(Role::Hrd->value));
        Gate::define('approve-disiplin', fn ($user) => (bool) ($user->karyawan?->punyaBawahan() || $user->hasRole(Role::Hrd->value)));
        Gate::define('buat-sanksi', fn ($user) => $user->hasRole(Role::Hrd->value) || $user->hasRole(Role::Direktur->value));
        Gate::define('kelola-inventaris', fn ($user) => count($user->timTeknis()) > 0);
        Gate::define('kerjakan-tiket', fn ($user) => count($user->timTeknis()) > 0);

        // Absensi.
        Gate::define('absen', fn ($user) => $user->karyawan !== null);
        Gate::define('kelola-jadwal', fn ($user) => ($user->karyawan?->jabatan?->level?->value ?? 0) >= 2);
        // Laporan absensi: HRD, Staff HR, dan Admin Sistem (via Gate::before). Koordinator TIDAK.
        Gate::define('lihat-rekap-absensi', fn ($user) => $user->hasRole(Role::Hrd->value) || $user->hasRole(Role::StaffHr->value));
        Gate::define('kelola-pengaturan-absensi', fn ($user) => false); // Admin-only via Gate::before
    }
}
