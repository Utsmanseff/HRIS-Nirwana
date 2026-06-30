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

    public function test_anchor_cuti_dari_pkwt_pertama(): void
    {
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Percobaan, 'tanggal_mulai' => '2024-01-01']);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => '2024-04-01']);
        Kontrak::factory()->for($kar)->create(['jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => '2025-04-01']);
        $this->assertEquals('2024-04-01', $kar->anchorCutiTahunan()->toDateString());
    }
}
