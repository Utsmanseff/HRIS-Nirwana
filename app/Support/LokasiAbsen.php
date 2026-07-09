<?php

namespace App\Support;

use App\Models\PengaturanAbsensi;

/** Validasi lokasi absen. Otoritas server: koordinat/akurasi dari client diverifikasi ulang di sini. */
class LokasiAbsen
{
    /** Jarak dua titik (meter) via Haversine. */
    public static function jarakMeter(float $lat1, float $long1, float $lat2, float $long2): float
    {
        $r = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLong = deg2rad($long2 - $long1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLong / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** True bila titik dalam radius kantor. */
    public static function dalamRadius(float $lat, float $long, PengaturanAbsensi $p): bool
    {
        return self::jarakMeter($lat, $long, (float) $p->office_lat, (float) $p->office_long) <= $p->radius_m;
    }

    /** True bila akurasi (meter) tidak lebih buruk dari batas. */
    public static function akurasiDiterima(float $akurasi, PengaturanAbsensi $p): bool
    {
        return $akurasi <= $p->max_akurasi_m;
    }

    /**
     * Heuristik lemah fake-GPS — MENANDAI bukan menjamin (§10 spec).
     *
     * @return list<string> flag (kosong bila tak ada indikasi)
     */
    public static function heuristik(float $akurasi): array
    {
        $flags = [];
        if ($akurasi <= 1.0) {          // akurasi "terlalu sempurna" ≈ 0 m
            $flags[] = 'akurasi_sempurna';
        }

        return $flags;
    }
}
