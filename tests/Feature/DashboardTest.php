<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_stat_karyawan_aktif_dan_belum_tetap(): void
    {
        // +1 karyawan milik userSdm() (aktif, kontrak dari factory)
        $user = $this->userSdm();
        $tetap = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $tetap->id, 'jenis' => 'tetap', 'tanggal_mulai' => now()->subYear(), 'tanggal_akhir' => null]);
        $pkwt = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $pkwt->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(2), 'tanggal_akhir' => now()->addMonths(10)]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Karyawan Aktif')
            ->assertSee('Belum Tetap');
    }

    public function test_stat_kontrak_30_hari_dan_terlewat(): void
    {
        $user = $this->userSdm();
        $akan = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $akan->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(11), 'tanggal_akhir' => now()->addDays(10)]);
        $lewat = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $lewat->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(13), 'tanggal_akhir' => now()->subDays(5)]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Kontrak ≤ 30 Hari')
            ->assertSee('Terlewat');
        // assertSee nama karyawan di tabel pengingat — ditambah Task 4
    }

    public function test_user_tanpa_kelola_sdm_tidak_lihat_stat(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Karyawan Aktif')
            ->assertSee('Profil');
    }
}
