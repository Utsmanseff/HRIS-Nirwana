<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LogoAsetTest extends TestCase
{
    public function test_komponen_logo_render_gambar_wordmark_asli(): void
    {
        $html = Blade::render('<x-logo />');
        $this->assertStringContainsString('RSU22Nirwana.png', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }

    public function test_manifest_pakai_icon_png_persegi_purpose_any(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $srcs = array_column($manifest['icons'], 'src');
        $this->assertContains('/img/android-chrome-192x192.png', $srcs);
        $this->assertContains('/img/android-chrome-512x512.png', $srcs);
        foreach ($manifest['icons'] as $ic) {
            $this->assertSame('any', $ic['purpose']); // bukan maskable — file belum ada safe-zone
        }
    }
}
