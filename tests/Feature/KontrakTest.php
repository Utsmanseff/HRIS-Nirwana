<?php

namespace Tests\Feature;

use App\Enums\JenisKontrak;
use App\Models\Karyawan;
use App\Models\Kontrak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KontrakTest extends TestCase
{
    use RefreshDatabase;

    public function test_milik_karyawan_dan_cast_enum(): void
    {
        $k = Kontrak::factory()->create(['jenis' => JenisKontrak::Pkwt]);
        $this->assertSame(JenisKontrak::Pkwt, $k->fresh()->jenis);
        $this->assertNotNull($k->karyawan);
    }

    public function test_anchor_masa_kerja_lewati_percobaan(): void
    {
        // Masa kerja dihitung dari kontrak nyata pertama (PKWT/Tetap), percobaan dilewati.
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Percobaan, 'tanggal_mulai' => '2024-01-01']);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => '2024-04-01']);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => '2025-04-01']);
        $this->assertEquals('2024-04-01', $kar->anchorMasaKerja()->toDateString());
    }

    public function test_anchor_periode_ikut_kontrak_nyata_terbaru(): void
    {
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => '2024-04-01']);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Tetap, 'tanggal_mulai' => '2025-08-22']);
        $this->assertEquals('2025-08-22', $kar->anchorPeriodeCuti(\Illuminate\Support\Carbon::parse('2026-01-01'))->toDateString());
        // Sebelum kontrak terbaru berlaku → pakai kontrak yang sudah berlaku.
        $this->assertEquals('2024-04-01', $kar->anchorPeriodeCuti(\Illuminate\Support\Carbon::parse('2025-01-01'))->toDateString());
    }
}
