<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Inventaris\InventarisIndex;
use App\Models\Aset;
use App\Models\KategoriInventaris;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventarisIndexTest extends TestCase
{
    use RefreshDatabase;

    private function userTim(TimTeknis $tim): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole($tim === TimTeknis::It ? Role::It->value : Role::Atem->value);

        return $u;
    }

    public function test_hanya_aset_tim_sendiri(): void
    {
        $katIt = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $katAtem = KategoriInventaris::factory()->create(['tim' => TimTeknis::Atem]);
        $asetIt = Aset::factory()->create(['kategori_inventaris_id' => $katIt->id, 'nama' => 'PC-IT']);
        Aset::factory()->create(['kategori_inventaris_id' => $katAtem->id, 'nama' => 'Ventilator-X']);

        Livewire::actingAs($this->userTim(TimTeknis::It))
            ->test(InventarisIndex::class)
            ->assertSee('PC-IT')
            ->assertDontSee('Ventilator-X');
    }

    public function test_filter_pencarian(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->create(['kategori_inventaris_id' => $kat->id, 'nama' => 'Printer Lantai 2', 'kode' => 'IT-0009']);
        Aset::factory()->create(['kategori_inventaris_id' => $kat->id, 'nama' => 'Router Inti', 'kode' => 'IT-0010']);

        Livewire::actingAs($this->userTim(TimTeknis::It))
            ->test(InventarisIndex::class)
            ->set('q', 'Router')
            ->assertSee('Router Inti')
            ->assertDontSee('Printer Lantai 2');
    }
}
