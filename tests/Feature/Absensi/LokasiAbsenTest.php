<?php

namespace Tests\Feature\Absensi;

use App\Models\PengaturanAbsensi;
use App\Support\LokasiAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiAbsenTest extends TestCase
{
    use RefreshDatabase;

    public function test_jarak_meter_titik_sama_nol(): void
    {
        $this->assertEqualsWithDelta(0.0, LokasiAbsen::jarakMeter(-6.9, 107.6, -6.9, 107.6), 0.001);
    }

    public function test_jarak_meter_kira_kira_benar(): void
    {
        // ~111 m per 0.001 derajat lintang.
        $d = LokasiAbsen::jarakMeter(-6.9000000, 107.6, -6.9010000, 107.6);
        $this->assertEqualsWithDelta(111.0, $d, 2.0);
    }

    public function test_dalam_radius_dan_akurasi(): void
    {
        $p = PengaturanAbsensi::ambil(); // radius 100, max akurasi 30, titik -6.9147440 / 107.6098100

        $this->assertTrue(LokasiAbsen::dalamRadius((float) $p->office_lat, (float) $p->office_long, $p));
        $this->assertFalse(LokasiAbsen::dalamRadius(-6.9200000, 107.6098100, $p)); // ~585 m
        $this->assertTrue(LokasiAbsen::akurasiDiterima(20.0, $p));
        $this->assertFalse(LokasiAbsen::akurasiDiterima(45.0, $p));
    }

    public function test_heuristik_menandai_akurasi_terlalu_sempurna(): void
    {
        $this->assertContains('akurasi_sempurna', LokasiAbsen::heuristik(0.5));
        $this->assertSame([], LokasiAbsen::heuristik(12.0));
    }
}
