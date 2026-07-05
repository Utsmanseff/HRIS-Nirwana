<?php

namespace Tests\Feature\Cuti;

use App\Enums\OrgUnitTipe;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use App\Support\RantaiApproval;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RantaiApprovalSusunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Bangun Direktorat > Bidang > Unit, isi kepala tiap tingkat, kembalikan [dir, kabid, koor, unit]. */
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

    /** Jadikan seorang karyawan pemegang sebuah role (buat user + assign). */
    private function beriRole(Karyawan $kar, Role $role): void
    {
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole($role->value);
    }

    public function test_staff_naik_koordinator_kabid_lalu_hrd(): void
    {
        $h = $this->hierarki();
        $staff = Karyawan::factory()->staffUnit($h['unit'])->create();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiApproval::susun($staff);

        $this->assertSame(
            [$h['koor']->id, $h['kabid']->id, $hrd->id],
            $steps->pluck('approver.id')->all(),
        );
        $this->assertSame(
            [PeranApproval::Koordinator, PeranApproval::Kabid, PeranApproval::Hrd],
            $steps->pluck('peran')->all(),
        );
        $this->assertSame([1, 2, 3], $steps->pluck('urutan')->all());
    }

    public function test_koordinator_naik_kabid_lalu_hrd(): void
    {
        $h = $this->hierarki();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiApproval::susun($h['koor']);

        $this->assertSame([$h['kabid']->id, $hrd->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Kabid, PeranApproval::Hrd], $steps->pluck('peran')->all());
    }

    public function test_kabid_langsung_hrd_tanpa_direktur(): void
    {
        $h = $this->hierarki();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiApproval::susun($h['kabid']);

        $this->assertSame([$hrd->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Hrd], $steps->pluck('peran')->all());
    }

    public function test_pemohon_hrd_hanya_direktur(): void
    {
        $h = $this->hierarki();
        $hrdKar = Karyawan::factory()->staffUnit($h['unit'])->create();
        $this->beriRole($hrdKar, Role::Hrd);
        $this->beriRole($h['direktur'], Role::Direktur);

        $steps = RantaiApproval::susun($hrdKar);

        $this->assertSame([$h['direktur']->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Direktur], $steps->pluck('peran')->all());
    }

    public function test_tanpa_atasan_langsung_hrd(): void
    {
        // Karyawan di unit tanpa kepala di atasnya (staff unit tanpa induk kepala).
        $unit = OrgUnit::create(['nama' => 'Mandiri', 'tipe' => OrgUnitTipe::Unit->value]);
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiApproval::susun($staff);

        $this->assertSame([$hrd->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Hrd], $steps->pluck('peran')->all());
    }

    public function test_kepala_nonaktif_di_skip(): void
    {
        $h = $this->hierarki();
        // Koordinator unit nonaktif → staff harus lompat ke Kabid.
        $h['koor']->update(['status' => \App\Enums\StatusKaryawan::Nonaktif->value]);
        $staff = Karyawan::factory()->staffUnit($h['unit'])->create();
        $hrd = Karyawan::factory()->create();
        $this->beriRole($hrd, Role::Hrd);

        $steps = RantaiApproval::susun($staff);

        $this->assertSame([$h['kabid']->id, $hrd->id], $steps->pluck('approver.id')->all());
    }
}
