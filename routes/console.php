<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat kontrak & SIP → notifikasi HRD, sekali sehari.
Schedule::command('sdm:kirim-pengingat')->dailyAt('07:00')->withoutOverlapping();

// Pengingat pemeliharaan aset (H-14) → notifikasi tim pemilik, sekali sehari.
Schedule::command('inventaris:kirim-pengingat')->dailyAt('07:05')->withoutOverlapping();

// Auto-bentuk jadwal (non-destruktif) bulan berjalan + 2 depan dari template pola.
Schedule::command('absensi:bentuk-jadwal')->dailyAt('07:10')->withoutOverlapping();

// Retensi foto absensi: hapus foto > 3 bulan (baris absensi tetap untuk laporan/penggajian).
Schedule::command('absensi:bersihkan-foto')->dailyAt('02:30')->withoutOverlapping();
