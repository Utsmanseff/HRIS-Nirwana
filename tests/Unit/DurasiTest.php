<?php

namespace Tests\Unit;

use App\Support\Durasi;
use PHPUnit\Framework\TestCase;

class DurasiTest extends TestCase
{
    public function test_kurang_dari_sejam_tetap_menit(): void
    {
        $this->assertSame('0m', Durasi::label(0));
        $this->assertSame('45m', Durasi::label(45));
    }

    public function test_jam_bulat_tanpa_sisa_menit(): void
    {
        $this->assertSame('1j', Durasi::label(60));
        $this->assertSame('2j', Durasi::label(120));
    }

    public function test_jam_dengan_sisa_menit(): void
    {
        $this->assertSame('1j 30m', Durasi::label(90));
        $this->assertSame('7j 5m', Durasi::label(425));
    }

    public function test_null_dan_negatif_jadi_nol(): void
    {
        $this->assertSame('0m', Durasi::label(null));
        $this->assertSame('0m', Durasi::label(-15));
    }
}
