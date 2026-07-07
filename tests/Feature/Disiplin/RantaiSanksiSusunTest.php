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

    /** Direktorat > Bidang > Unit, kepala tiap tingkat. */
    private function hierarki(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();

        return compact('dir', 'bidang', 'unit', 'direktur', 'kabid', 'koor');
    }

    private function beriRole(Karyawan $kar, Role $role): void
    {
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole($role->value);
    }

    public function test_pengusul_koordinator_ke_kabid_lalu_hrd(): void
    {
        $h = $this->hierarki();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiSanksi::susun($h['koor']);

        $this->assertSame([$h['kabid']->id, $hrd->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Kabid, PeranApproval::Hrd], $steps->pluck('peran')->all());
        $this->assertSame([1, 2], $steps->pluck('urutan')->all());
    }

    public function test_pengusul_kabid_langsung_hrd(): void
    {
        $h = $this->hierarki();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiSanksi::susun($h['kabid']);

        $this->assertSame([$hrd->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Hrd], $steps->pluck('peran')->all());
    }

    public function test_pengusul_direktur_langsung_hrd_direktur_bukan_approver(): void
    {
        $h = $this->hierarki();
        $this->beriRole($h['direktur'], Role::Direktur);
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiSanksi::susun($h['direktur']);

        $this->assertSame([$hrd->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Hrd], $steps->pluck('peran')->all());
    }

    public function test_pengusul_hrd_self_terbit_langsung(): void
    {
        $h = $this->hierarki();
        $hrdKar = Karyawan::factory()->pimpinanUnit($h['bidang'], 3)->create();
        $this->beriRole($hrdKar, Role::Hrd);

        $steps = RantaiSanksi::susun($hrdKar);

        $this->assertSame([$hrdKar->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Hrd], $steps->pluck('peran')->all());
    }
}
