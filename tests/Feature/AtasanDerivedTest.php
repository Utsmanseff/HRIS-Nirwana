<?php

namespace Tests\Feature;

use App\Enums\OrgUnitTipe;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtasanDerivedTest extends TestCase
{
    use RefreshDatabase;

    /** Bangun pohon: Direktorat(dir) > Bidang(kabid) > Unit(koordinator + staff). */
    private function pohon(): array
    {
        $dirUnit = OrgUnit::factory()->create(['tipe' => OrgUnitTipe::Direktur, 'parent_id' => null]);
        $bidang = OrgUnit::factory()->create(['tipe' => OrgUnitTipe::Bidang, 'parent_id' => $dirUnit->id]);
        $unit = OrgUnit::factory()->create(['tipe' => OrgUnitTipe::Unit, 'parent_id' => $bidang->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dirUnit, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();

        return compact('direktur', 'kabid', 'koor', 'staff', 'unit', 'bidang');
    }

    public function test_atasan_staff_adalah_koordinator(): void
    {
        $p = $this->pohon();
        $this->assertEquals($p['koor']->id, $p['staff']->atasanDerived()->id);
    }

    public function test_atasan_koordinator_adalah_kabid(): void
    {
        $p = $this->pohon();
        $this->assertEquals($p['kabid']->id, $p['koor']->atasanDerived()->id);
    }

    public function test_atasan_kabid_adalah_direktur(): void
    {
        $p = $this->pohon();
        $this->assertEquals($p['direktur']->id, $p['kabid']->atasanDerived()->id);
    }

    public function test_direktur_tanpa_atasan(): void
    {
        $p = $this->pohon();
        $this->assertNull($p['direktur']->atasanDerived());
    }

    public function test_koordinator_kosong_atasan_staff_naik_ke_kabid(): void
    {
        $p = $this->pohon();
        $p['koor']->update(['status' => 'nonaktif']);
        $this->assertEquals($p['kabid']->id, $p['staff']->fresh()->atasanDerived()->id);
    }

    public function test_tanpa_org_unit_atasan_null(): void
    {
        // Karyawan yang jabatannya tak punya org_unit → org_unit_id null → atasan null.
        $unit = OrgUnit::factory()->create();
        $k = Karyawan::factory()->staffUnit($unit)->create();
        $k->orgUnit()->associate(null);
        $k->save();
        $this->assertNull($k->atasanDerived());
    }

    public function test_org_unit_auto_set_dari_jabatan(): void
    {
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);
        // Buat karyawan hanya dengan jabatan_id, tanpa org_unit_id eksplisit.
        $k = Karyawan::factory()->create(['jabatan_id' => $jab->id, 'org_unit_id' => null]);
        $this->assertEquals($unit->id, $k->fresh()->org_unit_id);
    }
}
