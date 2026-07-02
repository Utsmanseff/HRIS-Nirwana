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

class PenggunaUnlinkTest extends TestCase
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

    public function test_unlink_melepas_karyawan_dan_mencabut_semua_role(): void
    {
        $karyawan = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $karyawan->id]);
        $user->assignRole(Role::Karyawan->value);

        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $user->id)
            ->call('unlink');

        $user->refresh();
        $this->assertNull($user->karyawan_id);
        $this->assertCount(0, $user->roles);
        // Data karyawan kembali bisa diklaim (tidak ada user yang menautkannya).
        $this->assertNull(User::where('karyawan_id', $karyawan->id)->first());
    }

    public function test_user_tanpa_tautan_tidak_error(): void
    {
        $user = User::factory()->create(['karyawan_id' => null]);

        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $user->id)
            ->call('unlink')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh()->karyawan_id);
    }

    public function test_tidak_bisa_unlink_akun_sendiri(): void
    {
        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $this->admin->id)
            ->call('unlink')
            ->assertHasErrors('kelola');

        $this->assertNotNull($this->admin->fresh()->karyawan_id);
    }
}
