<?php

namespace Tests\Feature\Tiket;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Tiket\LaporanTiket;
use App\Models\Tiket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanTiketTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_tampil_tiket_tim(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::It->value);
        Tiket::factory()->tim(TimTeknis::It)->create(['judul' => 'Tiket IT laporan']);
        Tiket::factory()->tim(TimTeknis::Atem)->create(['judul' => 'Tiket ATEM laporan']);

        Livewire::actingAs($u)->test(LaporanTiket::class)
            ->assertSee('Tiket IT laporan')
            ->assertDontSee('Tiket ATEM laporan');
    }
}
