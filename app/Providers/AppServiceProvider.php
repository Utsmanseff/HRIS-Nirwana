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
        Gate::define('kelola-jadwal-divisi', fn ($user) => ($user->karyawan?->jabatan?->level?->value ?? 0) >= 2);
        Gate::define('kelola-cuti', fn ($user) => $user->hasRole(Role::Hrd->value));
    }
}
