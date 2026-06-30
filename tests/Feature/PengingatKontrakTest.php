<?php

namespace Tests\Feature;

use App\Enums\JenisKontrak;
use App\Enums\SeverityPengingat;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Support\PengingatKontrak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengingatKontrakTest extends TestCase
{
    use RefreshDatabase;

    public function test_pkwt_20_hari_lagi_akan_berakhir(): void
    {
        $kar = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => now()->subYear(), 'tanggal_akhir' => now()->addDays(20)]);
        $list = PengingatKontrak::semua();
        $this->assertCount(1, $list);
        $this->assertSame(SeverityPengingat::AkanBerakhir, $list->first()->severity);
    }

    public function test_pkwt_lewat_jadi_terlewat(): void
    {
        $kar = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => now()->subYears(2), 'tanggal_akhir' => now()->subDays(5)]);
        $this->assertSame(SeverityPengingat::Terlewat, PengingatKontrak::semua()->first()->severity);
    }

    public function test_karyawan_tetap_tanpa_pengingat(): void
    {
        $kar = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Tetap, 'tanggal_mulai' => now()->subYear(), 'tanggal_akhir' => null]);
        $this->assertCount(0, PengingatKontrak::semua());
    }

    public function test_karyawan_nonaktif_gugur(): void
    {
        $kar = Karyawan::factory()->create(['status' => StatusKaryawan::Nonaktif]);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => now()->subYears(2), 'tanggal_akhir' => now()->subDays(5)]);
        $this->assertCount(0, PengingatKontrak::semua());
    }

    public function test_hanya_kontrak_terakhir_dihitung(): void
    {
        $kar = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => now()->subYears(2), 'tanggal_akhir' => now()->subDays(10)]);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Tetap, 'tanggal_mulai' => now()->subMonth(), 'tanggal_akhir' => null]);
        $this->assertCount(0, PengingatKontrak::semua());
    }
}
