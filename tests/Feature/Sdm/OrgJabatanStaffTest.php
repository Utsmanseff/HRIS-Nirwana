<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\OrgStruktur;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class OrgJabatanStaffTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_tambah_jabatan_staff_ke_unit(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Farmasi', 'parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->set('jNama', 'Apoteker')
            ->call('simpanJabatanStaff')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jabatan', [
            'nama' => 'Apoteker', 'level' => 1, 'org_unit_id' => $unit->id,
        ]);
    }

    public function test_daftar_jabatan_staff_unit_tampil_di_panel(): void
    {
        $unit = OrgUnit::factory()->create(['parent_id' => null]);
        Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1, 'nama' => 'Perawat']);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->assertSee('Perawat');
    }

    public function test_nama_jabatan_staff_wajib(): void
    {
        $unit = OrgUnit::factory()->create(['parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->set('jNama', '')
            ->call('simpanJabatanStaff')
            ->assertHasErrors(['jNama']);
    }
}
