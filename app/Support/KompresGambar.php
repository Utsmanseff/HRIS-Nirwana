<?php

namespace App\Support;

use RuntimeException;

/** Konversi gambar (png/jpg/webp) → WEBP terkompres via GD. Dipakai dokumen SDM;
 *  nanti reusable foto absensi (Fase 4). */
class KompresGambar
{
    public static function keWebp(string $isi, int $kualitas = 80): string
    {
        $img = @imagecreatefromstring($isi);
        if ($img === false) {
            throw new RuntimeException('Berkas bukan gambar yang dikenali.');
        }

        imagepalettetotruecolor($img);
        ob_start();
        imagewebp($img, null, $kualitas);
        $webp = ob_get_clean();
        imagedestroy($img);

        return $webp;
    }
}
