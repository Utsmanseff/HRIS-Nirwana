<?php

namespace Tests\Unit;

use App\Support\KompresGambar;
use PHPUnit\Framework\TestCase;

class KompresGambarTest extends TestCase
{
    public function test_konversi_png_ke_webp(): void
    {
        // PNG kecil 10x10 dibuat via GD.
        $img = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        $webp = KompresGambar::keWebp($png);

        // Magic bytes WEBP: RIFF....WEBP
        $this->assertSame('RIFF', substr($webp, 0, 4));
        $this->assertSame('WEBP', substr($webp, 8, 4));
    }
}
