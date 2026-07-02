<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class KaryawanDetailTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_detail_menampilkan_data_pribadi_dan_kontak(): void
    {
        $kar = Karyawan::factory()->create([
            'nama_lengkap' => 'Siti Rahmawati',
            'nip' => '2024.03.0117',
            'nik' => '3275016804920009',
            'no_hp' => '081223456789',
        ]);

        $this->actingAs($this->userSdm())->get('/sdm/karyawan/'.$kar->id)
            ->assertOk()
            ->assertSee('Siti Rahmawati')
            ->assertSee('2024.03.0117')
            ->assertSee('3275016804920009')
            ->assertSee('081223456789');
    }

    public function test_detail_menampilkan_sip_bila_ada(): void
    {
        $kar = Karyawan::factory()->withSip()->create();

        $this->actingAs($this->userSdm())->get('/sdm/karyawan/'.$kar->id)
            ->assertOk()
            ->assertSee($kar->sip_nomor);
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($user)->get('/sdm/karyawan/'.$kar->id)->assertForbidden();
    }
}
