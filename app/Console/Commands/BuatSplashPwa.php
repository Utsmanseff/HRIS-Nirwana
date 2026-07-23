<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pwa:splash {--font= : Path font TTF (default resources/fonts/PlusJakartaSans-ExtraBold.ttf)}')]
#[Description('Render splash screen iOS (apple-touch-startup-image) dari logo + teks NirwanaHRIS')]
class BuatSplashPwa extends Command
{
    /**
     * Resolusi potret iPhone yang didukung, dalam piksel device.
     * Device di luar daftar ini menampilkan latar polos tanpa logo — konsekuensi
     * yang diterima sadar (lihat spec). Menambah model baru: tambah entri di sini,
     * jalankan ulang command, lalu tambahkan tag di pwa-head.blade.php.
     *
     * @var list<array{int,int}>
     */
    public const UKURAN = [
        [750, 1334],    // iPhone SE / 8 / 7 / 6s
        [828, 1792],    // iPhone 11 / XR
        [1170, 2532],   // iPhone 12 / 13 / 14
        [1179, 2556],   // iPhone 14 Pro / 15 / 16
        [1284, 2778],   // iPhone 12-13 Pro Max / 15 Plus
        [1290, 2796],   // iPhone 14-16 Pro Max
    ];

    private const LATAR = [0x0c, 0x13, 0x12];   // --bg-app gelap
    private const PUTIH = [0xff, 0xff, 0xff];
    private const HIJAU = [0x6f, 0xd5, 0x93];   // --brand-300, sama dgn appbar

    public function handle(): int
    {
        $font = $this->option('font') ?: resource_path('fonts/PlusJakartaSans-ExtraBold.ttf');
        if (! is_file($font)) {
            $this->error("Font tidak ditemukan: {$font}");

            return self::FAILURE;
        }

        $logoPath = public_path('img/android-chrome-512x512.png');
        if (! is_file($logoPath)) {
            $this->error("Logo tidak ditemukan: {$logoPath}");

            return self::FAILURE;
        }

        $dir = public_path('img/splash');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach (self::UKURAN as [$w, $h]) {
            $this->render($logoPath, $font, $w, $h, "{$dir}/splash-{$w}x{$h}.png");
            $this->line("  splash-{$w}x{$h}.png");
        }

        $this->info(count(self::UKURAN).' berkas splash dibuat di public/img/splash/');

        return self::SUCCESS;
    }

    private function render(string $logoPath, string $font, int $w, int $h, string $tujuan): void
    {
        $kanvas = imagecreatetruecolor($w, $h);
        imagefilledrectangle($kanvas, 0, 0, $w, $h, imagecolorallocate($kanvas, ...self::LATAR));
        imagealphablending($kanvas, true);

        // Logo: 38% sisi terpendek, digeser ke atas agar blok logo+teks terlihat di tengah.
        $logo = imagecreatefrompng($logoPath);
        $sisi = (int) (min($w, $h) * 0.38);
        $logoX = (int) (($w - $sisi) / 2);
        $logoY = (int) ($h / 2 - $sisi * 0.72);
        imagecopyresampled($kanvas, $logo, $logoX, $logoY, 0, 0, $sisi, $sisi, imagesx($logo), imagesy($logo));
        imagedestroy($logo);

        // Teks dua warna "Nirwana"+"HRIS" (meniru appbar), ditengahkan sebagai satu blok.
        $ukuran = max(14, (int) ($sisi * 0.15));
        $lebarNirwana = $this->lebarTeks($font, $ukuran, 'Nirwana');
        $lebarTotal = $this->lebarTeks($font, $ukuran, 'NirwanaHRIS');
        $teksX = (int) (($w - $lebarTotal) / 2);
        $teksY = $logoY + $sisi + $ukuran + (int) ($sisi * 0.16);

        imagettftext($kanvas, $ukuran, 0, $teksX, $teksY, imagecolorallocate($kanvas, ...self::PUTIH), $font, 'Nirwana');
        imagettftext($kanvas, $ukuran, 0, $teksX + $lebarNirwana, $teksY, imagecolorallocate($kanvas, ...self::HIJAU), $font, 'HRIS');

        imagepng($kanvas, $tujuan);
        imagedestroy($kanvas);
    }

    private function lebarTeks(string $font, int $ukuran, string $teks): int
    {
        $bbox = imagettfbbox($ukuran, 0, $font, $teks);

        return (int) abs($bbox[2] - $bbox[0]);
    }
}
