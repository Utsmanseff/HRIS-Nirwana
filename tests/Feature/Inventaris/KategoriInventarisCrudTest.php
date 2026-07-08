<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Inventaris\KategoriInventaris as KategoriComp;
use App\Models\KategoriInventaris;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KategoriInventarisCrudTest extends TestCase
{
    use RefreshDatabase;

    private function userIt(): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::It->value);

        return $u;
    }

    public function test_simpan_kategori_baru(): void
    {
        Livewire::actingAs($this->userIt())
            ->test(KategoriComp::class)
            ->set('nama', 'Laptop')
            ->set('tim', TimTeknis::It->value)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kategori_inventaris', ['nama' => 'Laptop', 'tim' => 'it']);
    }

    public function test_tak_bisa_pilih_tim_lain(): void
    {
        Livewire::actingAs($this->userIt())
            ->test(KategoriComp::class)
            ->set('nama', 'Ventilator')
            ->set('tim', TimTeknis::Atem->value)
            ->call('simpan')
            ->assertHasErrors('tim');

        $this->assertDatabaseMissing('kategori_inventaris', ['nama' => 'Ventilator']);
    }

    public function test_toggle_aktif(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It, 'aktif' => true]);
        Livewire::actingAs($this->userIt())
            ->test(KategoriComp::class)
            ->call('toggleAktif', $kat->id);
        $this->assertFalse($kat->fresh()->aktif);
    }
}
