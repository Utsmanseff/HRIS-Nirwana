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
        OrgUnit::factory()->create(['nama' => 'Divisi IT', 'tipe' => 'divisi', 'parent_id' => $bidang->id]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->assertSee('Penunjang Medik')
            ->assertSee('Divisi IT');
    }
}
