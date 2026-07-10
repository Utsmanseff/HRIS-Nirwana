<?php

namespace Tests\Feature\Absensi;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateAbsensiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_absen_butuh_karyawan(): void
    {
        $tanpa = User::factory()->create(['karyawan_id' => null]);
        $kar = Karyawan::factory()->create();
        $dengan = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->assertFalse($tanpa->can('absen'));
        $this->assertTrue($dengan->can('absen'));
    }

    public function test_kelola_jadwal_butuh_level_2(): void
    {
        $staff = $this->userLevel(1);
        $koor = $this->userLevel(2);

        $this->assertFalse($staff->can('kelola-jadwal'));
        $this->assertTrue($koor->can('kelola-jadwal'));
    }

    public function test_lihat_rekap_hanya_hrd_staffhr_admin(): void
    {
        $hrd = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $hrd->assignRole(Role::Hrd->value);

        $staffHr = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $staffHr->assignRole(Role::StaffHr->value);

        $koor = $this->userLevel(2); // koordinator murni, tanpa role

        $this->assertTrue($hrd->can('lihat-rekap-absensi'));
        $this->assertTrue($staffHr->can('lihat-rekap-absensi'));
        $this->assertFalse($koor->can('lihat-rekap-absensi')); // koordinator TIDAK
    }

    private function userLevel(int $level): User
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        $jab = \App\Models\Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => $level]);
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id]);

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }
}
