<?php

namespace Tests\Feature;

use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanTest extends TestCase
{
    use RefreshDatabase;

    public function test_nip_unik(): void
    {
        Karyawan::factory()->create(['nip' => '1990.04.21.001']);
        $this->expectException(QueryException::class);
        Karyawan::factory()->create(['nip' => '1990.04.21.001']);
    }

    public function test_relasi_org_jabatan_dan_atasan_derived(): void
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $k = Karyawan::factory()->staffUnit($unit)->create();
        $this->assertNotNull($k->orgUnit);
        $this->assertNotNull($k->jabatan);
        $this->assertEquals($koor->id, $k->atasanDerived()->id);
    }

    public function test_scope_aktif(): void
    {
        Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        Karyawan::factory()->create(['status' => StatusKaryawan::Nonaktif]);
        $this->assertCount(1, Karyawan::aktif()->get());
    }
}
