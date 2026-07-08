<?php

namespace App\Support;

use RuntimeException;

/** Konversi gambar (png/jpg/webp) → WEBP terkompres via GD, dengan opsi downscale
 *  dimensi (cap sisi terpanjang) agar file kecil & server ringan. */
class KompresGambar
{
    public static function keWebp(string $isi, int $kualitas = 80, ?int $maksSisi = null): string
    {
        $img = @imagecreatefromstring($isi);
        if ($img === false) {
            throw new RuntimeException('Berkas bukan gambar yang dikenali.');
        }

        imagepalettetotruecolor($img);

        if ($maksSisi !== null) {
            $img = self::downscale($img, $maksSisi);
        }

        ob_start();
        imagewebp($img, null, $kualitas);
        $webp = ob_get_clean();
        imagedestroy($img);

        return $webp;
    }

    /** @return \GdImage */
    private static function downscale(\GdImage $img, int $maksSisi): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $sisiTerpanjang = max($w, $h);
        if ($sisiTerpanjang <= $maksSisi) {
            return $img;
        }
        $rasio = $maksSisi / $sisiTerpanjang;
        $wBaru = (int) round($w * $rasio);
        $hBaru = (int) round($h * $rasio);
        $baru = imagecreatetruecolor($wBaru, $hBaru);
        imagecopyresampled($baru, $img, 0, 0, 0, 0, $wBaru, $hBaru, $w, $h);
        imagedestroy($img);

        return $baru;
    }
}
