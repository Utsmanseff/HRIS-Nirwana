<?php

namespace Tests\Feature\Sistem;

use App\Enums\Role;
use App\Livewire\Sistem\PenggunaKelola;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PenggunaRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $this->admin->assignRole(Role::AdminSistem->value);
        $this->actingAs($this->admin);
    }

    public function test_assign_dan_cabut_role(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->assignRole(Role::Karyawan->value);

        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $user->id)
            ->assertSet('rolePilihan', [Role::Karyawan->value])
            ->set('rolePilihan', [Role::Karyawan->value, Role::StaffHr->value])
            ->call('simpanRole');

        $this->assertEqualsCanonicalizing(
            [Role::Karyawan->value, Role::StaffHr->value],
            $user->fresh()->roles->pluck('name')->all()
        );
    }

    public function test_role_di_luar_daftar_enum_diabaikan(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $user->id)
            ->set('rolePilihan', ['Role Palsu', Role::It->value])
            ->call('simpanRole');

        $this->assertSame([Role::It->value], $user->fresh()->roles->pluck('name')->all());
    }

    public function test_tidak_bisa_ubah_role_akun_sendiri(): void
    {
        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $this->admin->id)
            ->set('rolePilihan', [])
            ->call('simpanRole')
            ->assertHasErrors('kelola');

        $this->assertTrue($this->admin->fresh()->hasRole(Role::AdminSistem->value));
    }
}
