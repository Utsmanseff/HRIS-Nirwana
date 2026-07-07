<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateApproveDisiplinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_kepala_unit_punya_bawahan_boleh(): void
    {
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        Karyawan::factory()->staffUnit($unit)->create();
        $user = User::factory()->create(['karyawan_id' => $kabid->id]);

        $this->assertTrue($user->can('approve-disiplin'));
    }

    public function test_hrd_tanpa_bawahan_tetap_boleh(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Hrd->value);

        $this->assertTrue($user->can('approve-disiplin'));
    }

    public function test_staff_biasa_tak_boleh(): void
    {
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value]);
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $user = User::factory()->create(['karyawan_id' => $staff->id]);

        $this->assertFalse($user->can('approve-disiplin'));
    }
}
