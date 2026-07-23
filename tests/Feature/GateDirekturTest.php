<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateDirekturTest extends TestCase
{
    use RefreshDatabase;

    /** Direktorat sebagai akar + satu unit anak, supaya punyaBawahan() bernilai true. */
    private function buatDirektur(): User
    {
        $akar = OrgUnit::factory()->create(['tipe' => 'direktur', 'parent_id' => null]);
        $anak = OrgUnit::factory()->create(['tipe' => 'unit', 'parent_id' => $akar->id]);
        Karyawan::factory()->staffUnit($anak)->create();

        $dir = Karyawan::factory()->pimpinanUnit($akar, 4)->create();

        return User::factory()->create(['karyawan_id' => $dir->id]);
    }

    private function buatKoordinator(): User
    {
        $unit = OrgUnit::factory()->create(['tipe' => 'unit']);
        Karyawan::factory()->staffUnit($unit)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();

        return User::factory()->create(['karyawan_id' => $koor->id]);
    }

    public function test_direktur_ditolak_gate_yang_dicabut(): void
    {
        $this->seed(RoleSeeder::class);
        $dir = $this->buatDirektur();

        $this->assertFalse($dir->can('kelola-jadwal'));
        $this->assertFalse($dir->can('usul-disiplin'));
        $this->assertFalse($dir->can('ajukan-cuti'));
        $this->assertFalse($dir->can('lihat-sanksi-sendiri'));
        $this->assertFalse($dir->can('lihat-jadwal-sendiri'));
    }

    public function test_direktur_tetap_boleh_absen(): void
    {
        $this->seed(RoleSeeder::class);
        $this->assertTrue($this->buatDirektur()->can('absen'));
    }

    public function test_koordinator_tidak_kehilangan_gate(): void
    {
        $this->seed(RoleSeeder::class);
        $koor = $this->buatKoordinator();

        $this->assertTrue($koor->can('kelola-jadwal'));
        $this->assertTrue($koor->can('usul-disiplin'));
        $this->assertTrue($koor->can('ajukan-cuti'));
        $this->assertTrue($koor->can('lihat-sanksi-sendiri'));
        $this->assertTrue($koor->can('lihat-jadwal-sendiri'));
        $this->assertTrue($koor->can('absen'));
    }

    public function test_admin_sistem_tetap_tembus_semua(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['karyawan_id' => null]);
        $admin->assignRole(Role::AdminSistem->value);

        foreach (['kelola-jadwal', 'usul-disiplin', 'ajukan-cuti', 'lihat-sanksi-sendiri', 'lihat-jadwal-sendiri'] as $gate) {
            $this->assertTrue($admin->can($gate), "admin seharusnya tembus $gate");
        }
    }
}
