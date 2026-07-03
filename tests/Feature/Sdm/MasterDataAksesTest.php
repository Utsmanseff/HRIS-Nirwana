<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class MasterDataAksesTest extends TestCase
{
    use RefreshDatabase;

    private function userDgnPermission(?string $permission): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        if ($permission) {
            $perm = SpatiePermission::findOrCreate($permission, 'web');
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    public function test_punya_kelola_sdm_boleh_akses_struktur(): void
    {
        $user = $this->userDgnPermission(Permission::KelolaSdm->value);

        $this->actingAs($user)->get('/sdm/struktur')->assertOk();
    }

    public function test_tanpa_kelola_sdm_ditolak(): void
    {
        $user = $this->userDgnPermission(null);

        $this->actingAs($user)->get('/sdm/struktur')->assertForbidden();
    }
}
