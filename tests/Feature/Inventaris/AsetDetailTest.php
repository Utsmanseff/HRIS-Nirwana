<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Livewire\Inventaris\AsetDetail;
use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\KategoriInventaris;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AsetDetailTest extends TestCase
{
    use RefreshDatabase;

    private function userIt(): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole(Role::It->value);

        return $u;
    }

    private function asetIt(): Aset
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);

        return Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
    }

    public function test_tim_lain_ditolak(): void
    {
        $katAtem = KategoriInventaris::factory()->create(['tim' => TimTeknis::Atem]);
        $asetAtem = Aset::factory()->create(['kategori_inventaris_id' => $katAtem->id]);

        Livewire::actingAs($this->userIt())
            ->test(AsetDetail::class, ['aset' => $asetAtem])
            ->assertForbidden();
    }

    public function test_mutasi_update_lokasi_dan_riwayat(): void
    {
        $aset = $this->asetIt();
        $unitBaru = OrgUnit::factory()->create();

        Livewire::actingAs($this->userIt())
            ->test(AsetDetail::class, ['aset' => $aset])
            ->set('mutasiUnitId', $unitBaru->id)
            ->set('mutasiCatatan', 'pindah ruang')
            ->call('simpanMutasi')
            ->assertHasNoErrors();

        $this->assertSame($unitBaru->id, $aset->fresh()->org_unit_id);
        $this->assertDatabaseHas('mutasi_aset', ['aset_id' => $aset->id, 'ke_unit_id' => $unitBaru->id]);
    }

    public function test_tambah_jadwal(): void
    {
        $aset = $this->asetIt();

        Livewire::actingAs($this->userIt())
            ->test(AsetDetail::class, ['aset' => $aset])
            ->set('jNama', 'Kalibrasi')
            ->set('jInterval', 6)
            ->call('simpanJadwal')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jadwal_pemeliharaan', ['aset_id' => $aset->id, 'nama' => 'Kalibrasi', 'interval_bulan' => 6]);
    }

    public function test_tandai_jadwal_selesai_update_terakhir(): void
    {
        $aset = $this->asetIt();
        $j = JadwalPemeliharaan::factory()->for($aset)->create(['terakhir_dilakukan' => null]);

        Livewire::actingAs($this->userIt())
            ->test(AsetDetail::class, ['aset' => $aset])
            ->call('tandaiJadwalSelesai', $j->id);

        $this->assertNotNull($j->fresh()->terakhir_dilakukan);
    }
}
