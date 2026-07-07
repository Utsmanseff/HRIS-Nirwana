<?php

namespace Tests\Feature\Disiplin;

use App\Enums\Role;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Livewire\Disiplin\KelolaDisiplin;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaDisiplinTest extends TestCase
{
    use RefreshDatabase;

    private function hrd(): User
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Hrd->value);

        return $user;
    }

    public function test_hrd_bisa_akses_dan_lihat_semua_sanksi(): void
    {
        $user = $this->hrd();
        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Sp1)->create([
            'status' => StatusSanksi::Diterbitkan,
        ]);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->assertOk()
            ->assertSee($sanksi->karyawan->nama_lengkap);
    }

    public function test_non_hrd_non_direktur_ditolak(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Karyawan->value);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)->assertForbidden();
    }

    public function test_filter_status_menyaring(): void
    {
        $user = $this->hrd();
        $terbit = SanksiDisiplin::factory()->create(['status' => StatusSanksi::Diterbitkan]);
        $tolak = SanksiDisiplin::factory()->create(['status' => StatusSanksi::Ditolak]);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->set('filterStatus', StatusSanksi::Diterbitkan->value)
            ->assertSee($terbit->karyawan->nama_lengkap)
            ->assertDontSee($tolak->karyawan->nama_lengkap);
    }
}
