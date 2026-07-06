<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Cuti\Persetujuan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PersetujuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_route_persetujuan_butuh_gate_approve_cuti(): void
    {
        // Staff biasa (tanpa bawahan, bukan HRD) → 403.
        $unit = OrgUnit::factory()->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $user = User::factory()->create(['karyawan_id' => $staff->id]);
        $user->assignRole('Karyawan');

        $this->actingAs($user)->get('/cuti/persetujuan')->assertForbidden();
    }

    public function test_hrd_bisa_buka_persetujuan(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole('HRD');

        $this->actingAs($user)->get('/cuti/persetujuan')->assertOk();

        Livewire::actingAs($user)->test(Persetujuan::class)
            ->assertOk()
            ->assertSet('tab', 'perlu-aksi');
    }
}
