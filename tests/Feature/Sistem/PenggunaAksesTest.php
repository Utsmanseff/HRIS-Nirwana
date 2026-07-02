<?php

namespace Tests\Feature\Sistem;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenggunaAksesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function buatUser(string $role): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_sistem_bisa_buka_halaman_pengguna(): void
    {
        $this->actingAs($this->buatUser(Role::AdminSistem->value))
            ->get('/sistem/pengguna')
            ->assertOk()
            ->assertSee('Pengguna & Role');
    }

    public function test_staff_hr_ditolak_403(): void
    {
        $this->actingAs($this->buatUser(Role::StaffHr->value))
            ->get('/sistem/pengguna')
            ->assertForbidden();
    }

    public function test_karyawan_biasa_ditolak_403(): void
    {
        $this->actingAs($this->buatUser(Role::Karyawan->value))
            ->get('/sistem/pengguna')
            ->assertForbidden();
    }

    public function test_link_sidebar_hanya_muncul_untuk_yang_berhak(): void
    {
        $this->actingAs($this->buatUser(Role::AdminSistem->value))
            ->get('/dashboard')
            ->assertSee('/sistem/pengguna');

        $this->actingAs($this->buatUser(Role::Karyawan->value))
            ->get('/dashboard')
            ->assertDontSee('/sistem/pengguna');
    }
}
