<?php

namespace Tests\Feature\Tiket;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Inventaris\AsetDetail;
use App\Models\Aset;
use App\Models\KategoriInventaris;
use App\Models\Tiket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiwayatPerbaikanAsetTest extends TestCase
{
    use RefreshDatabase;

    public function test_riwayat_tampil_tiket_aset(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::It->value);

        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
        Tiket::factory()->tim(TimTeknis::It)->create(['inventaris_id' => $aset->id, 'judul' => 'Servis kipas']);
        Tiket::factory()->tim(TimTeknis::It)->create(['inventaris_id' => null, 'judul' => 'Tiket lain']);

        Livewire::actingAs($u)->test(AsetDetail::class, ['aset' => $aset])
            ->set('tab', 'riwayat')
            ->assertSee('Servis kipas')
            ->assertDontSee('Tiket lain');
    }
}
