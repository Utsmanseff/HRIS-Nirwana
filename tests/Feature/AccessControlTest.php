<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function userKlaim(): User
    {
        return User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
    }

    public function test_tamu_diarahkan_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_belum_klaim_diarahkan_ke_klaim(): void
    {
        $u = User::factory()->create(['karyawan_id' => null]);
        $this->actingAs($u)->get('/dashboard')->assertRedirect('/klaim');
    }

    public function test_user_klaim_lihat_dashboard(): void
    {
        $u = $this->userKlaim();
        $u->assignRole(Role::Karyawan->value);
        $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_rute_sdm_butuh_permission(): void
    {
        $k = $this->userKlaim();
        $k->assignRole(Role::Karyawan->value);
        $this->actingAs($k)->get('/sdm/karyawan')->assertForbidden();

        $hr = $this->userKlaim();
        $hr->assignRole(Role::StaffHr->value);
        $this->actingAs($hr)->get('/sdm/karyawan')->assertOk();
    }
}
