<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Evaluasi telat & pulang cepat dari snapshot shift. Tangani shift malam (lintas hari). */
class EvaluasiAbsensi
{
    /** Menit telat (dari jam mulai shift) bila masuk melewati toleransi; else 0. */
    public static function telatMenit(Carbon $jamMasuk, string $shiftMulai, int $toleransi): int
    {
        $mulai = $jamMasuk->copy()->setTimeFromTimeString($shiftMulai);
        $batasToleransi = $mulai->copy()->addMinutes($toleransi);

        return $jamMasuk->greaterThan($batasToleransi)
            ? (int) $mulai->diffInMinutes($jamMasuk)
            : 0;
    }

    /** Menit pulang cepat (dari jam selesai shift) bila pulang sebelum selesai; else 0. */
    public static function pulangCepatMenit(Carbon $jamMasuk, Carbon $jamPulang, string $shiftMulai, string $shiftSelesai): int
    {
        $selesai = $jamMasuk->copy()->setTimeFromTimeString($shiftSelesai);
        if ($shiftSelesai < $shiftMulai) {   // lintas hari → jam selesai di hari berikutnya
            $selesai->addDay();
        }

        return $jamPulang->lessThan($selesai)
            ? (int) $jamPulang->diffInMinutes($selesai)
            : 0;
    }
}
