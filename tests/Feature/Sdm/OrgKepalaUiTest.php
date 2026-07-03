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
}
