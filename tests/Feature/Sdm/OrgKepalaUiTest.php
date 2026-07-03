<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\OrgStruktur;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class OrgKepalaUiTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_node_menampilkan_nama_kepala(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Farmasi', 'parent_id' => null]);
        Karyawan::factory()->pimpinanUnit($unit, 2)->create(['nama_lengkap' => 'Budi Kepala']);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->assertSee('Budi Kepala');
    }

    public function test_node_tanpa_kepala_tampilkan_placeholder(): void
    {
        OrgUnit::factory()->create(['nama' => 'IT Kosong', 'parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->assertSee('Belum ada kepala');
    }

    public function test_cari_lalu_pilih_kepala_dari_existing(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Radiologi', 'parent_id' => null]);
        $calon = Karyawan::factory()->staffUnit($unit)->create(['nama_lengkap' => 'Sari Calon', 'nip' => 'RAD-9']);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaSetKepala', $unit->id)
            ->set('cariKaryawan', 'Sari')
            ->assertSee('Sari Calon')
            ->call('pilihKepala', $calon->id)
            ->assertHasNoErrors();

        $this->assertEquals($calon->id, $unit->fresh()->kepala()->id);
    }

    public function test_tambah_cepat_buat_karyawan_lalu_jadi_kepala(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Gizi', 'parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaSetKepala', $unit->id)
            ->set('tcNip', 'GZ-001')
            ->set('tcNama', 'Dewi Baru')
            ->set('tcTanggalMasuk', '2024-01-10')
            ->call('tambahCepatKepala')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('karyawan', ['nip' => 'GZ-001', 'nama_lengkap' => 'Dewi Baru']);
        $kepala = $unit->fresh()->kepala();
        $this->assertNotNull($kepala);
        $this->assertSame('GZ-001', $kepala->nip);
    }

    public function test_tambah_cepat_nip_dan_nama_wajib(): void
    {
        $unit = OrgUnit::factory()->create(['parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaSetKepala', $unit->id)
            ->set('tcNip', '')
            ->set('tcNama', '')
            ->call('tambahCepatKepala')
            ->assertHasErrors(['tcNip', 'tcNama']);
    }
}
