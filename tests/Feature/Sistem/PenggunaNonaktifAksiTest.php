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

class PenggunaNonaktifAksiTest extends TestCase
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

    public function test_toggle_nonaktif_lalu_aktif_lagi(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $component = Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $user->id)
            ->call('toggleAktif');

        $this->assertFalse($user->fresh()->akunAktif());

        $component->call('toggleAktif');

        $this->assertTrue($user->fresh()->akunAktif());
    }

    public function test_tidak_bisa_nonaktifkan_akun_sendiri(): void
    {
        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $this->admin->id)
            ->call('toggleAktif')
            ->assertHasErrors('kelola');

        $this->assertTrue($this->admin->fresh()->akunAktif());
    }
}
