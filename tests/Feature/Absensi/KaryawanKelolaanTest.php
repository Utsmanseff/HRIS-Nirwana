<?php

namespace Tests\Feature\Absensi;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanKelolaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_koordinator_memimpin_unitnya(): void
    {
        $bidang = OrgUnit::factory()->create(['tipe' => 'bidang', 'parent_id' => null]);
        $unit = OrgUnit::factory()->create(['tipe' => 'unit', 'parent_id' => $bidang->id]);

        $jabKoor = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 2]);
        $koor = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jabKoor->id]);

        $jabStaff = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);
        $staff = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jabStaff->id]);

        $dipimpin = $koor->unitDipimpin();
        $this->assertTrue($dipimpin->contains('id', $unit->id));

        $kelolaan = $koor->karyawanKelolaan()->pluck('id');
        $this->assertTrue($kelolaan->contains($staff->id));
        $this->assertTrue($kelolaan->contains($koor->id));   // dirinya termasuk
    }

    public function test_staff_tidak_memimpin_apa_pun(): void
    {
        $unit = OrgUnit::factory()->create(['tipe' => 'unit']);
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);
        $staff = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id]);

        $this->assertCount(0, $staff->unitDipimpin());
        $this->assertCount(0, $staff->karyawanKelolaan()->get());
    }

    public function test_shift_scope_untuk_unit(): void
    {
        $a = OrgUnit::factory()->create();
        $b = OrgUnit::factory()->create();
        Shift::factory()->for($a, 'orgUnit')->create();
        Shift::factory()->for($b, 'orgUnit')->create();

        $this->assertCount(1, Shift::untukUnit([$a->id])->get());
    }
}
