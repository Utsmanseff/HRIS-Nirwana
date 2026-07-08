<?php

namespace Tests\Feature\Tiket;

use App\Enums\Role;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Livewire\Tiket\TiketIndex;
use App\Models\Karyawan;
use App\Models\Tiket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TiketIndexTest extends TestCase
{
    use RefreshDatabase;

    private function userTim(Role $role): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole($role->value);

        return $u;
    }

    public function test_tim_lihat_antrian_tim_sendiri(): void
    {
        Tiket::factory()->tim(TimTeknis::It)->create(['judul' => 'Printer IT mati']);
        Tiket::factory()->tim(TimTeknis::Atem)->create(['judul' => 'Ventilator error']);

        Livewire::actingAs($this->userTim(Role::It))
            ->test(TiketIndex::class)
            ->assertSee('Printer IT mati')
            ->assertDontSee('Ventilator error');
    }

    public function test_filter_status(): void
    {
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Baru)->create(['judul' => 'Baru A']);
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Selesai)->create(['judul' => 'Selesai B']);

        Livewire::actingAs($this->userTim(Role::It))
            ->test(TiketIndex::class)
            ->set('status', 'baru')
            ->assertSee('Baru A')
            ->assertDontSee('Selesai B');
    }

    public function test_default_aktif_sembunyikan_selesai_semua_tampilkan(): void
    {
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Baru)->create(['judul' => 'Aktif A']);
        Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Selesai)->create(['judul' => 'Kelar B']);

        // Default 'aktif' → selesai tersembunyi.
        Livewire::actingAs($this->userTim(Role::It))
            ->test(TiketIndex::class)
            ->assertSee('Aktif A')
            ->assertDontSee('Kelar B')
            // 'Semua Status' (status='') → selesai muncul.
            ->set('status', '')
            ->assertSee('Aktif A')
            ->assertSee('Kelar B');
    }

    public function test_karyawan_biasa_lihat_tiket_saya(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $kar = Karyawan::factory()->create();
        $u->update(['karyawan_id' => $kar->id]);
        $u->assignRole(Role::Karyawan->value);

        Tiket::factory()->tim(TimTeknis::It)->create(['pelapor_id' => $kar->id, 'judul' => 'Laporan saya']);
        Tiket::factory()->tim(TimTeknis::It)->create(['pelapor_id' => null, 'judul' => 'Bukan saya']);

        Livewire::actingAs($u)->test(TiketIndex::class)
            ->assertSet('adalahTim', false)
            ->assertSee('Laporan saya')
            ->assertDontSee('Bukan saya');
    }
}
