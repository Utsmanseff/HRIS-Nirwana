<?php

namespace Tests\Unit;

use App\Support\KompresGambar;
use PHPUnit\Framework\TestCase;

class KompresGambarTest extends TestCase
{
    public function test_downscale_sisi_terpanjang(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD tidak tersedia.');
        }
        $img = imagecreatetruecolor(3000, 1500);
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        $webp = KompresGambar::keWebp($png, 70, 1600);
        $out = imagecreatefromstring($webp);
        $this->assertSame(1600, imagesx($out));
        $this->assertSame(800, imagesy($out));
        imagedestroy($out);
    }

    public function test_tanpa_maks_sisi_tak_resize(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD tidak tersedia.');
        }
        $img = imagecreatetruecolor(400, 200);
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        $webp = KompresGambar::keWebp($png);
        $out = imagecreatefromstring($webp);
        $this->assertSame(400, imagesx($out));
        imagedestroy($out);
    }
}
