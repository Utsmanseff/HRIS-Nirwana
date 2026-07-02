<?php

namespace Tests\Feature\Sdm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanSdmTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_instansi_tersedia_untuk_kop(): void
    {
        $this->assertSame('RSU Nirwana', config('instansi.nama'));
        $this->assertNotEmpty(config('instansi.alamat'));
        $this->assertSame('img/RSU22Nirwana.png', config('instansi.logo'));
    }
}
