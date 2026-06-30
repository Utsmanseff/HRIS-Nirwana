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

        // Kemampuan struktural (derived dari atasan_id + jabatan.level), bukan role.
        Gate::define('approve-cuti', fn ($user) => (bool) $user->karyawan?->bawahan()->exists());
        Gate::define('usul-disiplin', fn ($user) => (bool) $user->karyawan?->bawahan()->exists());
        Gate::define('kelola-jadwal-divisi', fn ($user) => ($user->karyawan?->jabatan?->level?->value ?? 0) >= 2);
    }
}
