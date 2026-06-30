<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
    }

    public function test_seed_8_role(): void
    {
        $this->seed(RoleSeeder::class);
        foreach (Role::cases() as $r) {
            $this->assertDatabaseHas('roles', ['name' => $r->value]);
        }
        $this->assertSame(8, SpatieRole::count());
    }

    public function test_admin_sistem_bypass_semua(): void
    {
        $this->seed(RoleSeeder::class);
        $u = $this->user();
        $u->assignRole(Role::AdminSistem->value);
        $this->assertTrue($u->can('kelola-sdm'));
        $this->assertTrue($u->can('apa-saja-yang-tidak-ada'));
    }

    public function test_staff_hr_kelola_sdm_tapi_tak_acc_cuti(): void
    {
        $this->seed(RoleSeeder::class);
        $u = $this->user();
        $u->assignRole(Role::StaffHr->value);
        $this->assertTrue($u->can('kelola-sdm'));
        $this->assertFalse($u->can('acc-cuti-final'));
    }

    public function test_gate_approve_cuti_derived_dari_bawahan(): void
    {
        $this->seed(RoleSeeder::class);
        $atasanKar = Karyawan::factory()->create();
        Karyawan::factory()->create(['atasan_id' => $atasanKar->id]); // punya 1 bawahan
        $atasan = User::factory()->create(['karyawan_id' => $atasanKar->id]);
        $atasan->assignRole(Role::Karyawan->value);
        $this->assertTrue($atasan->can('approve-cuti'));

        $biasaKar = Karyawan::factory()->create(); // tanpa bawahan
        $biasa = User::factory()->create(['karyawan_id' => $biasaKar->id]);
        $biasa->assignRole(Role::Karyawan->value);
        $this->assertFalse($biasa->can('approve-cuti'));
    }
}
