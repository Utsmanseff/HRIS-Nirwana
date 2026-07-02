<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PushSubscriptionController;
use App\Livewire\Auth\Klaim;
use App\Livewire\Auth\Login;
use App\Livewire\Profil;
use App\Livewire\Sdm\JabatanKelola;
use App\Livewire\Sdm\KaryawanDetail;
use App\Livewire\Sdm\KaryawanForm;
use App\Livewire\Sdm\KaryawanIndex;
use App\Livewire\Sdm\OrgStruktur;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Design-system styleguide — living reference of ported tokens & components.
Route::view('/styleguide', 'styleguide')->name('styleguide');

// --- Auth: guest-only routes ---
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});

// --- Auth: authenticated routes ---
Route::middleware('auth')->group(function () {
    Route::get('/klaim', Klaim::class)->name('klaim');
    // Logout didefinisikan di sini karena view klaim memakai route('logout').
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');

    // Simpan langganan Web Push milik device user (multi-device).
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
});

// --- App: auth + claimed gated routes ---
Route::middleware(['auth', 'claimed'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/profil', Profil::class)->name('profil');

    Route::middleware('permission:kelola-sdm')->group(function () {
        Route::get('/sdm/karyawan', KaryawanIndex::class)->name('sdm.karyawan');
        // '/tambah' harus SEBELUM '{karyawan}' agar tak ditelan route-model-binding.
        Route::get('/sdm/karyawan/tambah', KaryawanForm::class)->name('sdm.karyawan.tambah');
        Route::get('/sdm/karyawan/{karyawan}', KaryawanDetail::class)->name('sdm.karyawan.detail');
        Route::get('/sdm/jabatan', JabatanKelola::class)->name('sdm.jabatan');
        Route::get('/sdm/struktur', OrgStruktur::class)->name('sdm.struktur');
    });
});
