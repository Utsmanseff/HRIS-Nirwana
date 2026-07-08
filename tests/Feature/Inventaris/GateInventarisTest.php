<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateInventarisTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_boleh_kelola_inventaris_dan_tim_it(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::It->value);

        $this->assertTrue($u->can('kelola-inventaris'));
        $this->assertEquals([TimTeknis::It], $u->timTeknis());
    }

    public function test_karyawan_biasa_tak_boleh(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::Karyawan->value);

        $this->assertFalse($u->can('kelola-inventaris'));
        $this->assertSame([], $u->timTeknis());
    }

    public function test_admin_semua_tim(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::AdminSistem->value);

        $this->assertTrue($u->can('kelola-inventaris'));
        $this->assertEqualsCanonicalizing(TimTeknis::cases(), $u->timTeknis());
    }
}
