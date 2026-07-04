<?php

namespace Tests\Unit;

use App\Support\NamaFileLaporan;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class NamaFileLaporanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 7, 4, 15, 32, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tanpa_token_hanya_base_dan_tanggal(): void
    {
        $this->assertSame(
            'daftar-karyawan_20260704-1532.xlsx',
            NamaFileLaporan::buat('daftar-karyawan', [], 'xlsx'),
        );
    }

    public function test_dengan_token_filter_di_slug(): void
    {
        $this->assertSame(
            'daftar-karyawan_aktif_unit-farmasi_20260704-1532.pdf',
            NamaFileLaporan::buat('daftar-karyawan', ['aktif', 'Unit Farmasi'], 'pdf'),
        );
    }

    public function test_token_kosong_diabaikan(): void
    {
        $this->assertSame(
            'daftar-karyawan_aktif_20260704-1532.xlsx',
            NamaFileLaporan::buat('daftar-karyawan', ['', 'aktif', null], 'xlsx'),
        );
    }
}
