<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Support\EvaluasiAbsensi;
use Illuminate\Console\Command;

/**
 * pulang_cepat_menit disimpan saat absen pulang, jadi baris yang sudah telanjur
 * tercatat memakai aturan lama (selisih ke jam selesai shift) tidak ikut berubah
 * saat aturannya diperbaiki. Perintah ini menghitung ulang dari snapshot shift yang
 * memang tersimpan di barisnya sendiri, jadi aman diulang berkali-kali.
 */
class HitungUlangPulangCepat extends Command
{
    protected $signature = 'absensi:hitung-ulang-pulang-cepat {--uji-coba : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Hitung ulang pulang_cepat_menit sesi lama dengan aturan durasi jam kerja.';

    public function handle(): int
    {
        $ujiCoba = (bool) $this->option('uji-coba');
        $berubah = 0;
        $diperiksa = 0;

        Absensi::whereNotNull('shift_mulai')
            ->whereNotNull('shift_selesai')
            ->whereNotNull('jam_pulang')
            ->chunkById(200, function ($rows) use (&$berubah, &$diperiksa, $ujiCoba) {
                foreach ($rows as $a) {
                    $diperiksa++;
                    $baru = EvaluasiAbsensi::pulangCepatMenit(
                        $a->jam_masuk, $a->jam_pulang, $a->shift_mulai, $a->shift_selesai
                    );

                    if ((int) $a->pulang_cepat_menit === $baru) {
                        continue;
                    }

                    $this->line("#{$a->id} {$a->tanggal_kerja->format('Y-m-d')} · {$a->pulang_cepat_menit}m → {$baru}m");
                    $berubah++;

                    if (! $ujiCoba) {
                        $a->update(['pulang_cepat_menit' => $baru]);
                    }
                }
            });

        $this->info($ujiCoba
            ? "Uji coba: {$berubah} dari {$diperiksa} sesi akan berubah (tidak disimpan)."
            : "Selesai: {$berubah} dari {$diperiksa} sesi diperbarui.");

        return self::SUCCESS;
    }
}
