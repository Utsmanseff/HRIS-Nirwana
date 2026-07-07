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

    public function test_pilih_karyawan_set_tingkat_saran(): void
    {
        $user = $this->hrd();
        $target = Karyawan::factory()->create();
        // Sanksi aktif Teguran1 → saran berikutnya Teguran2.
        SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Teguran1)
            ->create(['karyawan_id' => $target->id]);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->call('pilihKaryawan', $target->id)
            ->assertSet('karyawanId', $target->id)
            ->assertSet('tingkat', (string) TingkatSanksi::Teguran2->value);
    }

    public function test_cari_karyawan_org_wide(): void
    {
        $user = $this->hrd();
        Karyawan::factory()->create(['nama_lengkap' => 'Zulfikar Rahman']);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->set('showForm', true)
            ->set('cariKaryawan', 'Zulfikar')
            ->assertSee('Zulfikar Rahman');
    }
}
