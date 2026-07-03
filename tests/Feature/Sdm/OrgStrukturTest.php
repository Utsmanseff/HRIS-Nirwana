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

class OrgStrukturTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_tree_menampilkan_akar_dan_anak(): void
    {
        $bidang = OrgUnit::factory()->create(['nama' => 'Penunjang Medik', 'tipe' => 'bidang', 'parent_id' => null]);
        OrgUnit::factory()->create(['nama' => 'Unit IT', 'tipe' => 'unit', 'parent_id' => $bidang->id]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->assertSee('Penunjang Medik')
            ->assertSee('Unit IT');
    }

    public function test_tambah_unit_dengan_parent(): void
    {
        $bidang = OrgUnit::factory()->create(['nama' => 'Umum', 'tipe' => 'bidang', 'parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('baru', $bidang->id)
            ->set('nama', 'Unit SDM')
            ->set('tipe', 'unit')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('org_units', ['nama' => 'Unit SDM', 'tipe' => 'unit', 'parent_id' => $bidang->id]);
    }

    public function test_ubah_unit(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Lama', 'tipe' => 'unit', 'parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('edit', $unit->id)
            ->assertSet('nama', 'Lama')
            ->set('nama', 'Baru')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('org_units', ['id' => $unit->id, 'nama' => 'Baru']);
    }

    public function test_nama_unit_wajib(): void
    {
        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('baru', null)
            ->set('nama', '')
            ->call('simpan')
            ->assertHasErrors(['nama']);
    }
}
