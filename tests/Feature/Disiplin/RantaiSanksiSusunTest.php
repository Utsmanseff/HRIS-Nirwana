<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use App\Support\RantaiSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RantaiSanksiSusunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Direktorat > Bidang > Unit + kepala tiap tingkat; Direktur & HRD ber-role. */
    private function hierarki(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $this->beriRole($direktur, Role::Direktur);

        return compact('dir', 'bidang', 'unit', 'direktur', 'kabid', 'koor');
    }

    private function beriRole(Karyawan $kar, Role $role): void
    {
        User::factory()->create(['karyawan_id' => $kar->id])->assignRole($role->value);
    }

    public function test_pengusul_koordinator_naik_ke_kabid_hrd_direktur(): void
    {
        $h = $this->hierarki();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiSanksi::susun($h['koor']);

        $this->assertSame([$h['kabid']->id, $hrd->id, $h['direktur']->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Kabid, PeranApproval::Hrd, PeranApproval::Direktur], $steps->pluck('peran')->all());
        $this->assertSame([1, 2, 3], $steps->pluck('urutan')->all());
    }

    public function test_pengusul_kabid_ke_hrd_direktur(): void
    {
        $h = $this->hierarki();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiSanksi::susun($h['kabid']);

        $this->assertSame([$hrd->id, $h['direktur']->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Hrd, PeranApproval::Direktur], $steps->pluck('peran')->all());
    }

    public function test_pengusul_hrd_langsung_direktur(): void
    {
        $h = $this->hierarki();
        $hrdKar = Karyawan::factory()->create();
        $this->beriRole($hrdKar, Role::Hrd);

        $steps = RantaiSanksi::susun($hrdKar);

        $this->assertSame([$h['direktur']->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Direktur], $steps->pluck('peran')->all());
    }

    public function test_pengusul_direktur_self_terbit(): void
    {
        $h = $this->hierarki();

        $steps = RantaiSanksi::susun($h['direktur']);

        $this->assertSame([$h['direktur']->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Direktur], $steps->pluck('peran')->all());
    }
}
