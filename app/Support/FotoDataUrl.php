<?php

namespace App\Support;

/**
 * Decoder data URL gambar dari kamera klien.
 *
 * Foto absen dikirim sebagai data URL di dalam panggilan Livewire, BUKAN lewat
 * `$wire.upload()`. Alasannya bukan selera: satu `upload()` sebenarnya tiga
 * permintaan HTTP berurutan (`_startUpload` → POST berkas → `_finishUpload`),
 * masing-masing boot Laravel penuh, lalu `simpan()` jadi yang keempat. Di HP
 * dengan jaringan seluler itu 0,3–0,7 detik hangus sebelum sebutir byte foto pun
 * terkirim. Satu panggilan dengan foto ikut di dalamnya memangkas semuanya jadi satu.
 *
 * Konsekuensinya: aturan validasi `image` milik Laravel tak lagi ikut jalan, jadi
 * pemeriksaannya dilakukan di sini secara eksplisit.
 */
class FotoDataUrl
{
    /** Tipe yang boleh dikirim klien. PNG diterima karena Safari < 16.4 diam-diam
     *  memberi PNG saat diminta WebP; klien sudah mencoba WebP → JPEG lebih dulu. */
    private const TIPE = ['webp', 'jpeg', 'jpg', 'png'];

    /**
     * Kembalikan biner gambar, atau null bila bukan data URL gambar yang sah /
     * melebihi $maksBytes. Isi biner TIDAK diperiksa di sini — KompresGambar yang
     * memutuskan apakah ia benar-benar gambar.
     */
    public static function dekode(?string $dataUrl, int $maksBytes): ?string
    {
        if ($dataUrl === null || $dataUrl === '') {
            return null;
        }

        $tipe = implode('|', self::TIPE);
        if (! preg_match("~^data:image/($tipe);base64,~i", $dataUrl, $cocok)) {
            return null;
        }

        $base64 = substr($dataUrl, strlen($cocok[0]));

        // Tolak SEBELUM decode: base64 ~4/3 ukuran aslinya, jadi panjang string
        // sudah cukup untuk menolak kiriman raksasa tanpa mengalokasikan memorinya.
        if (strlen($base64) > (int) ceil($maksBytes * 4 / 3) + 4) {
            return null;
        }

        $biner = base64_decode($base64, true);
        if ($biner === false || $biner === '' || strlen($biner) > $maksBytes) {
            return null;
        }

        return $biner;
    }
}
