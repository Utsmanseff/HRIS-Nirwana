<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Auth\Klaim;
use App\Livewire\Auth\Login;
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
    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
});

// --- App: auth + claimed gated routes ---
Route::middleware(['auth', 'claimed'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::middleware('permission:kelola-sdm')->group(function () {
        // Placeholder; UI asli dibangun di Fase 1b.
        Route::view('/sdm/karyawan', 'dashboard')->name('sdm.karyawan');
    });
});
