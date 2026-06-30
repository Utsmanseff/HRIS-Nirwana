<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Enums\StatusKaryawan;
use App\Livewire\Auth\Klaim;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KlaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_search_hanya_aktif_belum_diklaim(): void
    {
        $u = User::factory()->create(['karyawan_id' => null]);
        $aktif = Karyawan::factory()->create(['nama_lengkap' => 'Budi Aktif', 'status' => StatusKaryawan::Aktif]);
        $nonaktif = Karyawan::factory()->create(['nama_lengkap' => 'Budi Nonaktif', 'status' => StatusKaryawan::Nonaktif]);
        $terpakai = Karyawan::factory()->create(['nama_lengkap' => 'Budi Terpakai']);
        User::factory()->create(['karyawan_id' => $terpakai->id]); // sudah diklaim

        Livewire::actingAs($u)->test(Klaim::class)
            ->set('q', 'Budi')
            ->assertSee('Budi Aktif')
            ->assertDontSee('Budi Nonaktif')
            ->assertDontSee('Budi Terpakai');
    }

    public function test_klaim_sukses_link_dan_role_karyawan(): void
    {
        $u = User::factory()->create(['karyawan_id' => null]);
        $kar = Karyawan::factory()->create();

        Livewire::actingAs($u)->test(Klaim::class)->call('klaim', $kar->id)->assertRedirect('/dashboard');

        $u->refresh();
        $this->assertEquals($kar->id, $u->karyawan_id);
        $this->assertTrue($u->hasRole(Role::Karyawan->value));
    }

    public function test_klaim_data_terpakai_ditolak(): void
    {
        $u = User::factory()->create(['karyawan_id' => null]);
        $kar = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $kar->id]); // sudah dipakai

        Livewire::actingAs($u)->test(Klaim::class)->call('klaim', $kar->id)->assertHasErrors('q');
        $this->assertNull($u->fresh()->karyawan_id);
    }
}
