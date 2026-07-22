<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\Shift;
use App\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanDetailLowonganTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_penunjuk_muncul_saat_lowongan_masih_terbuka(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unit->id]);
        $kar = Karyawan::factory()->create([
            'org_unit_id' => $unit->id, 'status' => 'nonaktif', 'tanggal_nonaktif' => now()->subDay()->toDateString(),
        ]);
        Jadwal::create([
            'karyawan_id' => $kar->id, 'tanggal' => now()->addDay()->toDateString(), 'shift_id' => $shift->id,
        ]);

        $this->actingAs($this->admin())->get('/sdm/karyawan/'.$kar->id)
            ->assertOk()
            ->assertSee('Jadwal belum dialihkan');
    }

    public function test_penunjuk_hilang_bila_tak_ada_jejak_jadwal(): void
    {
        $kar = Karyawan::factory()->create([
            'status' => 'nonaktif', 'tanggal_nonaktif' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($this->admin())->get('/sdm/karyawan/'.$kar->id)
            ->assertOk()
            ->assertDontSee('Jadwal belum dialihkan');
    }

    public function test_karyawan_aktif_tak_pernah_dapat_penunjuk(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unit->id]);
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        Jadwal::create([
            'karyawan_id' => $kar->id, 'tanggal' => now()->addDay()->toDateString(), 'shift_id' => $shift->id,
        ]);

        $this->actingAs($this->admin())->get('/sdm/karyawan/'.$kar->id)
            ->assertOk()
            ->assertDontSee('Jadwal belum dialihkan');
    }
}
