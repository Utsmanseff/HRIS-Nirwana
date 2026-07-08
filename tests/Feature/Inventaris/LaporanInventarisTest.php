<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Inventaris\LaporanInventaris;
use App\Models\Aset;
use App\Models\Karyawan;
use App\Models\KategoriInventaris;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanInventarisTest extends TestCase
{
    use RefreshDatabase;

    private function userIt(): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $u->assignRole(Role::It->value);

        return $u;
    }

    public function test_halaman_tampil_aset_tim_saja(): void
    {
        $katIt = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->create(['kategori_inventaris_id' => $katIt->id, 'nama' => 'PC Lab']);
        $katAtem = KategoriInventaris::factory()->create(['tim' => TimTeknis::Atem]);
        Aset::factory()->create(['kategori_inventaris_id' => $katAtem->id, 'nama' => 'Ventilator Z']);

        Livewire::actingAs($this->userIt())
            ->test(LaporanInventaris::class)
            ->assertSee('PC Lab')
            ->assertDontSee('Ventilator Z');
    }

    public function test_ekspor_aset_xlsx(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);

        $this->actingAs($this->userIt())
            ->get(route('inventaris.laporan.aset', ['format' => 'xlsx']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_ekspor_pemeliharaan_xlsx(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);

        $this->actingAs($this->userIt())
            ->get(route('inventaris.laporan.pemeliharaan', ['format' => 'xlsx']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_ekspor_aset_pdf(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);

        $this->actingAs($this->userIt())
            ->get(route('inventaris.laporan.aset'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
