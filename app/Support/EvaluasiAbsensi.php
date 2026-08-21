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

    /**
     * Menit pulang cepat = KEKURANGAN JAM KERJA terhadap durasi shift, bukan selisih
     * terhadap jam selesai shift.
     *
     * Dulu dibandingkan ke jam selesai, sehingga orang yang masuk lebih awal karena ada
     * kegiatan (shift 09-16, masuk 08, pulang 15) tetap dicap "pulang cepat 60m" padahal
     * jam kerjanya genap 7 jam. Sekarang: datang lebih awal boleh dipakai untuk pulang
     * lebih awal.
     *
     * Keterlambatan dikeluarkan dari hitungan karena sudah tercatat sendiri di
     * telat_menit — tanpa itu, orang yang telat 10 menit lalu pulang tepat jam selesai
     * kena dua label sekaligus untuk satu kejadian yang sama.
     */
    public static function pulangCepatMenit(Carbon $jamMasuk, Carbon $jamPulang, string $shiftMulai, string $shiftSelesai): int
    {
        $mulai = $jamMasuk->copy()->setTimeFromTimeString($shiftMulai);
        $selesai = $jamMasuk->copy()->setTimeFromTimeString($shiftSelesai);
        if ($shiftSelesai < $shiftMulai) {   // lintas hari → jam selesai di hari berikutnya
            $selesai->addDay();
        }

        $durasiShift = (int) $mulai->diffInMinutes($selesai);
        $durasiKerja = (int) $jamMasuk->diffInMinutes($jamPulang);
        $telatMentah = max(0, (int) $mulai->diffInMinutes($jamMasuk));   // 0 bila datang lebih awal

        return max(0, $durasiShift - $durasiKerja - $telatMentah);
    }
}
