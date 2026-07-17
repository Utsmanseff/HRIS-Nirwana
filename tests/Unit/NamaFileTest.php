<?php

namespace Tests\Unit;

use App\Support\NamaFile;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class NamaFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 7, 4, 15, 32));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_laporan_tanpa_token(): void
    {
        $this->assertSame('daftar-karyawan_20260704-1532.xlsx', NamaFile::laporan('daftar-karyawan', [], 'xlsx'));
    }

    public function test_laporan_dengan_token_di_slug(): void
    {
        $this->assertSame(
            'daftar-karyawan_aktif_unit-farmasi_20260704-1532.pdf',
            NamaFile::laporan('daftar-karyawan', ['aktif', 'Unit Farmasi'], 'pdf'),
        );
    }

    public function test_laporan_abaikan_token_kosong(): void
    {
        $this->assertSame('daftar-karyawan_aktif_20260704-1532.pdf', NamaFile::laporan('daftar-karyawan', ['aktif', '', null], 'pdf'));
    }

    /** Surat pakai TANGGAL SURAT (bukan waktu unduh) → unduh berkali-kali, nama tetap sama. */
    public function test_surat_pakai_tanggal_surat(): void
    {
        $this->assertSame(
            'surat-keterangan-cuti_andi-pelaksana_20260717.pdf',
            NamaFile::surat('surat-keterangan-cuti', ['Andi Pelaksana'], Carbon::create(2026, 7, 17), 'pdf'),
        );
    }

    public function test_surat_tanggal_null_pakai_hari_ini(): void
    {
        $this->assertSame(
            'surat-peringatan-sp-1_budi_20260704.pdf',
            NamaFile::surat('surat-peringatan-sp-1', ['Budi'], null, 'pdf'),
        );
    }
}
