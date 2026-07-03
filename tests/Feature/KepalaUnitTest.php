<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KepalaUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepala_adalah_level_tertinggi_aktif(): void
    {
        $unit = OrgUnit::factory()->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        Karyawan::factory()->staffUnit($unit)->create();
        $this->assertEquals($koor->id, $unit->kepala()->id);
    }

    public function test_unit_tanpa_pimpinan_kepala_null(): void
    {
        $unit = OrgUnit::factory()->create();
        Karyawan::factory()->staffUnit($unit)->create();
        $this->assertNull($unit->kepala());
    }

    public function test_kepala_nonaktif_diabaikan(): void
    {
        $unit = OrgUnit::factory()->create();
        Karyawan::factory()->pimpinanUnit($unit, 2)->create(['status' => 'nonaktif']);
        $this->assertNull($unit->kepala());
    }
}
