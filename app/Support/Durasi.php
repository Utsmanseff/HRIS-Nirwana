<?php

namespace App\Support;

/**
 * Format durasi menit jadi label yang enak dibaca manusia.
 *
 * Sebelumnya semua durasi ditampilkan dalam menit mentah ("Pulang cepat 120m"),
 * yang menyulitkan pembaca laporan menakar besarannya.
 */
class Durasi
{
    /** 0 → "0m" · 45 → "45m" · 60 → "1j" · 90 → "1j 30m" · 120 → "2j". */
    public static function label(?int $menit): string
    {
        $menit = max(0, (int) $menit);
        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        if ($jam === 0) {
            return $sisa.'m';
        }

        return $sisa === 0 ? $jam.'j' : $jam.'j '.$sisa.'m';
    }
}
