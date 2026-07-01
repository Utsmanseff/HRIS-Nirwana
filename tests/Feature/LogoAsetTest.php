<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LogoAsetTest extends TestCase
{
    public function test_komponen_logo_render_mark_persegi_bukan_wordmark(): void
    {
        $html = Blade::render('<x-logo />');
        // <x-logo> = mark persegi (android-chrome), dipakai di wadah persegi sidebar/login/appbar.
        $this->assertStringContainsString('android-chrome-192x192.png', $html);
        // Wordmark horizontal HANYA untuk kop ekspor — tidak boleh muncul di komponen ini.
        $this->assertStringNotContainsString('RSU22Nirwana.png', $html);
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
