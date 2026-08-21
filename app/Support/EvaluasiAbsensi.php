<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Evaluasi telat & pulang cepat dari snapshot shift. Tangani shift malam (lintas hari). */
class EvaluasiAbsensi
{
    /** Menit telat (dari jam mulai shift) bila masuk melewati toleransi; else 0. */
    public static function telatMenit(Carbon $jamMasuk, string $shiftMulai, int $toleransi): int
    {
        $mulai = self::jamMulaiTerdekat($jamMasuk, $shiftMulai);
        $batasToleransi = $mulai->copy()->addMinutes($toleransi);

        return $jamMasuk->greaterThan($batasToleransi)
            ? (int) $mulai->diffInMinutes($jamMasuk)
            : 0;
    }

    /**
     * Jam mulai shift pada tanggal yang paling masuk akal terhadap jam masuk.
     *
     * Menempelkan jam shift ke tanggal jam_masuk saja tidak cukup untuk shift malam:
     * pekerja shift 21:00 yang baru masuk 00:30 punya tanggal jam_masuk = hari
     * berikutnya, sehingga patokannya jadi 21:00 nanti malam dan dia tercatat "tidak
     * telat" padahal terlambat 3,5 jam. Selisih lebih dari 12 jam berarti kita memilih
     * hari yang salah → geser satu hari ke arah terdekat.
     */
    private static function jamMulaiTerdekat(Carbon $jamMasuk, string $shiftMulai): Carbon
    {
        $mulai = $jamMasuk->copy()->setTimeFromTimeString($shiftMulai);
        $selisih = (int) $mulai->diffInMinutes($jamMasuk);   // + = masuk sesudah, − = sebelum

        if ($selisih > 720) {
            $mulai->addDay();
        } elseif ($selisih < -720) {
            $mulai->subDay();
        }

        return $mulai;
    }

    /** Panjang shift dalam menit; lintas hari (mis. 21:00–07:00) dibungkus lewat modulo. */
    public static function durasiShiftMenit(string $shiftMulai, string $shiftSelesai): int
    {
        $keMenit = static function (string $jam): int {
            [$j, $m] = array_map('intval', explode(':', $jam));

            return $j * 60 + $m;
        };

        return ($keMenit($shiftSelesai) - $keMenit($shiftMulai) + 1440) % 1440;
    }

    /**
     * Menit pulang cepat = KEKURANGAN JAM KERJA terhadap panjang shift, dihitung dari
     * jam masuk yang sebenarnya — bukan selisih terhadap jam selesai shift.
     *
     * Dulu dibandingkan ke jam selesai, sehingga orang yang masuk lebih awal karena ada
     * kegiatan (shift 09-16, masuk 08, pulang 15) dicap "pulang cepat 60m" padahal jam
     * kerjanya genap 7 jam. Kewajibannya adalah memenuhi panjang shift: datang lebih
     * awal boleh pulang lebih awal, datang telat berarti pulangnya ikut mundur.
     *
     * Konsekuensi yang disengaja: keterlambatan yang masih dalam toleransi tetap
     * memperpendek jam kerja, jadi bisa muncul sebagai pulang cepat. Toleransi mengatur
     * label kedisiplinan (telat_menit), bukan kewajiban jam kerja.
     *
     * Rumusnya sengaja tidak menyentuh TANGGAL sama sekali — hanya panjang shift lawan
     * durasi kerja. Itu yang membuat shift malam aman termasuk saat orangnya baru masuk
     * lewat tengah malam; versi lama menempelkan jam shift ke tanggal jam_masuk sehingga
     * patokannya meleset sehari.
     */
    public static function pulangCepatMenit(Carbon $jamMasuk, Carbon $jamPulang, string $shiftMulai, string $shiftSelesai): int
    {
        $durasiShift = self::durasiShiftMenit($shiftMulai, $shiftSelesai);
        $durasiKerja = (int) $jamMasuk->diffInMinutes($jamPulang);

        return max(0, $durasiShift - $durasiKerja);
    }
}
