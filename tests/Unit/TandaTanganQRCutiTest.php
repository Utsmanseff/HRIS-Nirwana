<?php

namespace Tests\Unit;

use App\Models\PengajuanCuti;
use App\Support\TandaTanganQR;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TandaTanganQRCutiTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_cuti_mengandung_path_dan_signature(): void
    {
        $p = PengajuanCuti::factory()->create();
        $url = TandaTanganQR::urlCuti($p, 'pemohon');

        $this->assertStringContainsString('/verifikasi/cuti/'.$p->id.'/pemohon', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_sumber_beda_menghasilkan_signature_beda(): void
    {
        $p = PengajuanCuti::factory()->create();

        $this->assertNotSame(
            Str::after(TandaTanganQR::urlCuti($p, 'pemohon'), 'signature='),
            Str::after(TandaTanganQR::urlCuti($p, 'hrd'), 'signature='),
        );
    }

    public function test_qr_mengembalikan_png_data_uri(): void
    {
        $uri = TandaTanganQR::qr(TandaTanganQR::urlCuti(PengajuanCuti::factory()->create(), 'pemohon'));
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
    }
}
