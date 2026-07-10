<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Karyawan;
use App\Models\User;
use App\Support\NavMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class NavMenuTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $perms = []): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        foreach ($perms as $p) {
            $user->givePermissionTo(SpatiePermission::findOrCreate($p, 'web'));
        }

        return $user;
    }

    public function test_karyawan_biasa_hanya_lihat_item_tanpa_can(): void
    {
        $user = $this->user([Permission::AjukanCutiAbsen->value]);
        $ids = collect(NavMenu::untuk($user))->pluck('id')->all();

        $this->assertContains('beranda', $ids);
        $this->assertContains('cuti', $ids);
        $this->assertContains('profil', $ids);
        $this->assertNotContains('karyawan', $ids); // butuh kelola-sdm
        $this->assertNotContains('pengguna', $ids);  // butuh kelola-rbac
    }

    public function test_hrd_lihat_union_item_karyawan_dan_manajemen(): void
    {
        $user = $this->user([Permission::AjukanCutiAbsen->value, Permission::KelolaSdm->value]);
        $ids = collect(NavMenu::untuk($user))->pluck('id')->all();

        $this->assertContains('cuti', $ids);      // item karyawan
        $this->assertContains('karyawan', $ids);  // item manajemen (kelola-sdm)
        $this->assertContains('struktur', $ids);
    }

    public function test_item_registry_lengkap_punya_kunci_wajib(): void
    {
        $user = $this->user([Permission::KelolaSdm->value, Permission::KelolaRbac->value]);
        foreach (NavMenu::untuk($user) as $it) {
            $this->assertArrayHasKey('id', $it);
            $this->assertArrayHasKey('label', $it);
            $this->assertArrayHasKey('icon', $it);
            $this->assertArrayHasKey('route', $it);
        }
    }

    public function test_href_resolve_route_dan_placeholder(): void
    {
        $user = $this->user([Permission::AjukanCutiAbsen->value]);
        $items = collect(NavMenu::untuk($user))->keyBy('id');

        $this->assertSame(route('profil'), NavMenu::href($items['profil']));
        $this->assertSame(route('absensi'), NavMenu::href($items['absensi'])); // route terpasang (5-3)
    }
}
