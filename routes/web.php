<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Sdm\DokumenController;
use App\Http\Controllers\Sdm\LaporanSdmController;
use App\Livewire\Auth\Klaim;
use App\Http\Controllers\Cuti\LampiranController;
use App\Livewire\Cuti\CutiDetail;
use App\Livewire\Cuti\CutiForm;
use App\Livewire\Cuti\CutiIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Beranda;
use App\Livewire\Profil;
use App\Livewire\Sdm\KaryawanDetail;
use App\Livewire\Sdm\KaryawanForm;
use App\Livewire\Sdm\KaryawanIndex;
use App\Livewire\Sdm\OrgStruktur;
use App\Livewire\Disiplin\UsulDisiplin;
use App\Livewire\Sistem\PenggunaKelola;
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
Route::middleware(['auth', 'aktif'])->group(function () {
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
Route::middleware(['auth', 'aktif', 'claimed'])->group(function () {
    Route::get('/beranda', Beranda::class)->name('beranda');
    Route::redirect('/dashboard', '/beranda');
    Route::get('/profil', Profil::class)->name('profil');

    Route::get('/cuti', CutiIndex::class)->name('cuti');
    Route::get('/cuti/ajukan', CutiForm::class)->name('cuti.ajukan');
    Route::get('/cuti/persetujuan', \App\Livewire\Cuti\Persetujuan::class)
        ->middleware('can:approve-cuti')->name('cuti.persetujuan');
    Route::get('/cuti/kelola', \App\Livewire\Cuti\KelolaCuti::class)
        ->middleware('can:kelola-cuti')->name('cuti.kelola');
    Route::get('/cuti/laporan', \App\Livewire\Cuti\LaporanCuti::class)
        ->middleware('can:kelola-cuti')->name('cuti.laporan');
    Route::get('/cuti/laporan/pengajuan', [\App\Http\Controllers\Cuti\LaporanCutiController::class, 'pengajuan'])
        ->middleware('can:kelola-cuti')->name('cuti.laporan.pengajuan');
    Route::get('/cuti/laporan/saldo', [\App\Http\Controllers\Cuti\LaporanCutiController::class, 'saldo'])
        ->middleware('can:kelola-cuti')->name('cuti.laporan.saldo');
    Route::get('/cuti/{pengajuan}/lampiran', [LampiranController::class, 'lihat'])->name('cuti.lampiran');
    Route::get('/cuti/{pengajuan}', CutiDetail::class)->name('cuti.detail');

    Route::get('/disiplin', UsulDisiplin::class)
        ->middleware('can:usul-disiplin')->name('disiplin');
    Route::get('/disiplin/persetujuan', \App\Livewire\Disiplin\PersetujuanDisiplin::class)
        ->middleware('can:approve-disiplin')->name('disiplin.persetujuan');
    Route::get('/disiplin/kelola', \App\Livewire\Disiplin\KelolaDisiplin::class)
        ->middleware('can:buat-sanksi')->name('disiplin.kelola');
    Route::get('/disiplin/saya', \App\Livewire\Disiplin\SanksiSaya::class)->name('disiplin.saya');
    Route::get('/disiplin/{sanksi}/surat', [\App\Http\Controllers\Disiplin\SuratSanksiController::class, 'lihat'])
        ->name('disiplin.surat');

    Route::middleware('permission:kelola-sdm')->group(function () {
        Route::get('/sdm/karyawan', KaryawanIndex::class)->name('sdm.karyawan');
        // '/tambah' harus SEBELUM '{karyawan}' agar tak ditelan route-model-binding.
        Route::get('/sdm/karyawan/tambah', KaryawanForm::class)->name('sdm.karyawan.tambah');
        Route::get('/sdm/karyawan/{karyawan}', KaryawanDetail::class)->name('sdm.karyawan.detail');
        Route::get('/sdm/karyawan/{karyawan}/ubah', KaryawanForm::class)->name('sdm.karyawan.ubah');
        Route::get('/sdm/dokumen/{dokumen}', [DokumenController::class, 'unduh'])->name('sdm.dokumen.unduh');
        Route::get('/sdm/dokumen/{dokumen}/lihat', [DokumenController::class, 'lihat'])->name('sdm.dokumen.lihat');
        Route::get('/sdm/struktur', OrgStruktur::class)->name('sdm.struktur');
        Route::get('/sdm/laporan/karyawan', [LaporanSdmController::class, 'karyawan'])->name('sdm.laporan.karyawan');
        Route::get('/sdm/laporan/pengingat-kontrak', [LaporanSdmController::class, 'pengingatKontrak'])->name('sdm.laporan.pengingat');
    });

    Route::middleware('permission:kelola-rbac')->group(function () {
        Route::get('/sistem/pengguna', PenggunaKelola::class)->name('sistem.pengguna');
    });
});
