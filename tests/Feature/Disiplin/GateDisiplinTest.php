<?php

namespace Tests\Feature\Disiplin;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateDisiplinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_boleh_kelola_disiplin(): void
    {
        $hrd = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $hrd->assignRole(Role::Hrd->value);
        $this->assertTrue($hrd->can('kelola-disiplin'));
    }

    public function test_karyawan_biasa_tak_boleh_kelola_disiplin(): void
    {
        $biasa = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $biasa->assignRole(Role::Karyawan->value);
        $this->assertFalse($biasa->can('kelola-disiplin'));
    }
}
