<?php

namespace Tests\Unit;

use App\Models\SanksiDisiplin;
use App\Support\TandaTanganQR;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TandaTanganQRTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_signed_mengandung_path_dan_signature(): void
    {
        $s = SanksiDisiplin::factory()->create(['nomor_surat' => 'SP/1']);
        $url = TandaTanganQR::url($s, 'penerbit');

        $this->assertStringContainsString('/verifikasi/sanksi/'.$s->id.'/penerbit', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_sumber_beda_menghasilkan_signature_beda(): void
    {
        $s = SanksiDisiplin::factory()->create();

        $this->assertNotSame(
            Str::after(TandaTanganQR::url($s, 'penerbit'), 'signature='),
            Str::after(TandaTanganQR::url($s, 'kabid'), 'signature='),
        );
    }

    public function test_qr_mengembalikan_png_data_uri(): void
    {
        $uri = TandaTanganQR::qr('https://contoh.test/x');
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
    }
}
