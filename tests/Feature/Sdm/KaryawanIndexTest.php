<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\KaryawanIndex;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class KaryawanIndexTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_halaman_daftar_terbuka_dengan_permission(): void
    {
        $this->actingAs($this->userSdm())->get('/sdm/karyawan')->assertOk();
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/sdm/karyawan')->assertForbidden();
    }

    public function test_daftar_menampilkan_nama_dan_nip(): void
    {
        Karyawan::factory()->create(['nama_lengkap' => 'Siti Rahmawati', 'nip' => '2024.03.0117']);

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->assertSee('Siti Rahmawati')
            ->assertSee('2024.03.0117');
    }

    public function test_pagination_15_per_halaman(): void
    {
        Karyawan::factory()->count(20)->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->assertViewHas('karyawan', fn ($p) => $p->count() === 15);
    }
}
