<?php

namespace Tests\Feature;

use App\Console\Commands\BuatSplashPwa;
use Tests\TestCase;

class PwaAsetTest extends TestCase
{
    public function test_manifest_memakai_nama_nirwanahris(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('NirwanaHRIS', $manifest['name']);
        $this->assertSame('NirwanaHRIS', $manifest['short_name']);
        $this->assertSame('#0c1312', $manifest['background_color']);
    }

    public function test_pwa_splash_menghasilkan_enam_berkas_dengan_dimensi_benar(): void
    {
        $this->artisan('pwa:splash')->assertSuccessful();

        $this->assertCount(6, BuatSplashPwa::UKURAN);

        foreach (BuatSplashPwa::UKURAN as [$w, $h]) {
            $path = public_path("img/splash/splash-{$w}x{$h}.png");
            $this->assertFileExists($path);

            [$lebar, $tinggi] = getimagesize($path);
            $this->assertSame($w, $lebar, "lebar $path");
            $this->assertSame($h, $tinggi, "tinggi $path");
        }
    }

    public function test_pwa_splash_idempoten(): void
    {
        $this->artisan('pwa:splash')->assertSuccessful();
        $path = public_path('img/splash/splash-1170x2532.png');
        $sebelum = md5_file($path);

        $this->artisan('pwa:splash')->assertSuccessful();

        $this->assertSame($sebelum, md5_file($path), 'menjalankan ulang harus menghasilkan berkas identik');
    }

    public function test_pwa_splash_gagal_jelas_bila_font_hilang(): void
    {
        $this->artisan('pwa:splash', ['--font' => '/jalur/tidak/ada.ttf'])
            ->expectsOutputToContain('Font tidak ditemukan')
            ->assertFailed();
    }
}
