<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Models\Dokumen;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
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

    public function test_tab_kontrak_menampilkan_riwayat_dan_pengingat(): void
    {
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $kar->id, 'jenis' => 'percobaan', 'tanggal_mulai' => now()->subYear(), 'tanggal_akhir' => now()->subMonths(9)]);
        Kontrak::factory()->create(['karyawan_id' => $kar->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(9), 'tanggal_akhir' => now()->addDays(10)]);

        $this->actingAs($this->userSdm())->get('/sdm/karyawan/'.$kar->id.'?tab=kontrak')
            ->assertOk()
            ->assertSee('Riwayat Kontrak')
            ->assertSee('PKWT')
            ->assertSee('Percobaan')
            ->assertSee('H-10');
    }

    public function test_tab_dokumen_menampilkan_berkas(): void
    {
        $kar = Karyawan::factory()->create();
        Dokumen::create(['karyawan_id' => $kar->id, 'tipe' => 'ijazah', 'path' => 'dokumen/ijazah-s1.pdf', 'mime' => 'application/pdf', 'ukuran' => 1258291]);

        $this->actingAs($this->userSdm())->get('/sdm/karyawan/'.$kar->id.'?tab=dokumen')
            ->assertOk()
            ->assertSee('ijazah-s1.pdf')
            ->assertSee('1.2 MB');
    }

    public function test_tab_akun_menampilkan_role_bila_tertaut(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id, 'email' => 'siti.r@rsunirwana.id']);
        $user->assignRole(SpatieRole::findOrCreate('HRD', 'web'));

        $this->actingAs($this->userSdm())->get('/sdm/karyawan/'.$kar->id.'?tab=akun')
            ->assertOk()
            ->assertSee('siti.r@rsunirwana.id')
            ->assertSee('HRD');
    }

    public function test_tab_akun_tanpa_user_tampilkan_belum_tertaut(): void
    {
        $kar = Karyawan::factory()->create();

        $this->actingAs($this->userSdm())->get('/sdm/karyawan/'.$kar->id.'?tab=akun')
            ->assertOk()
            ->assertSee('Belum tertaut akun');
    }
}
