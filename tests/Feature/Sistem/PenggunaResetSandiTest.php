<?php

namespace Tests\Feature\Sistem;

use App\Enums\Role;
use App\Livewire\Sistem\PenggunaKelola;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PenggunaResetSandiTest extends TestCase
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

    public function test_reset_sandi_menghasilkan_sandi_sementara_yang_valid(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $component = Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $user->id)
            ->call('resetSandi');

        $sandi = $component->get('sandiSementara');

        $this->assertNotNull($sandi);
        $this->assertSame(12, strlen($sandi));
        $this->assertTrue(Hash::check($sandi, $user->fresh()->password));
    }

    public function test_tidak_bisa_reset_sandi_akun_sendiri(): void
    {
        $hashLama = $this->admin->password;

        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $this->admin->id)
            ->call('resetSandi')
            ->assertHasErrors('kelola')
            ->assertSet('sandiSementara', null);

        $this->assertSame($hashLama, $this->admin->fresh()->password);
    }
}
