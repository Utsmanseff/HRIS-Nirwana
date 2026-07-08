<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Inventaris\AsetForm;
use App\Models\Aset;
use App\Models\KategoriInventaris;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AsetFormTest extends TestCase
{
    use RefreshDatabase;

    private function userIt(): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::It->value);

        return $u;
    }

    public function test_tambah_aset(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Livewire::actingAs($this->userIt())
            ->test(AsetForm::class)
            ->set('kode', 'IT-0001')
            ->set('nama', 'PC Front Office')
            ->set('kategoriId', $kat->id)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('aset', ['kode' => 'IT-0001', 'nama' => 'PC Front Office']);
    }

    public function test_kode_wajib_unik(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->create(['kode' => 'IT-0001', 'kategori_inventaris_id' => $kat->id]);

        Livewire::actingAs($this->userIt())
            ->test(AsetForm::class)
            ->set('kode', 'IT-0001')
            ->set('nama', 'Duplikat')
            ->set('kategoriId', $kat->id)
            ->call('simpan')
            ->assertHasErrors('kode');
    }

    public function test_ubah_aset_isi_awal(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id, 'nama' => 'Lama']);

        Livewire::actingAs($this->userIt())
            ->test(AsetForm::class, ['aset' => $aset])
            ->assertSet('nama', 'Lama')
            ->set('nama', 'Baru')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame('Baru', $aset->fresh()->nama);
    }
}
