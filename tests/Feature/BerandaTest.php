<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class BerandaTest extends TestCase
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
        $user = $this->userSdm();
        $tetap = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $tetap->id, 'jenis' => 'tetap', 'tanggal_mulai' => now()->subYear(), 'tanggal_akhir' => null]);
        $pkwt = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $pkwt->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(2), 'tanggal_akhir' => now()->addMonths(10)]);

        $this->actingAs($user)->get('/beranda')
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

        $this->actingAs($user)->get('/beranda')
            ->assertOk()
            ->assertSee('Kontrak ≤ 30 Hari')
            ->assertSee('Terlewat')
            ->assertSee($akan->nama_lengkap)
            ->assertSee($lewat->nama_lengkap);
    }

    public function test_tabel_pengingat_urut_terlewat_dulu_dan_link_detail(): void
    {
        $user = $this->userSdm();
        $akan = Karyawan::factory()->create(['nama_lengkap' => 'Akan Berakhir']);
        Kontrak::factory()->create(['karyawan_id' => $akan->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(11), 'tanggal_akhir' => now()->addDays(10)]);
        $lewat = Karyawan::factory()->create(['nama_lengkap' => 'Sudah Lewat']);
        Kontrak::factory()->create(['karyawan_id' => $lewat->id, 'jenis' => 'pkwt', 'tanggal_mulai' => now()->subMonths(13), 'tanggal_akhir' => now()->subDays(5)]);

        $this->actingAs($user)->get('/beranda')->assertOk()
            ->assertSee(route('sdm.karyawan.detail', $lewat).'?tab=kontrak', false)
            ->assertSeeInOrder(['Sudah Lewat', 'Akan Berakhir']);
    }

    public function test_kartu_sip_tampil_bila_ada_yang_hampir_habis(): void
    {
        $user = $this->userSdm();
        Karyawan::factory()->withSip()->create([
            'nama_lengkap' => 'Perawat Sip',
            'sip_berlaku_akhir' => now()->addDays(12),
        ]);

        $this->actingAs($user)->get('/beranda')
            ->assertOk()
            ->assertSee('Pengingat SIP')
            ->assertSee('Perawat Sip');
    }

    public function test_user_tanpa_kelola_sdm_tidak_lihat_stat(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($user)->get('/beranda')
            ->assertOk()
            ->assertDontSee('Karyawan Aktif')
            ->assertSee('Profil');
    }

    public function test_karyawan_eligible_lihat_kartu_jatah_cuti(): void
    {
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->create([
            'karyawan_id' => $kar->id, 'jenis' => 'tetap',
            'tanggal_mulai' => now()->subYears(2), 'tanggal_akhir' => null,
        ]);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/beranda')
            ->assertOk()
            ->assertSee('Jatah')            // istilah UI "Jatah", bukan "Saldo"
            ->assertDontSee('Karyawan Aktif'); // bukan manajer → tak lihat stat SDM
    }

    public function test_dashboard_redirect_ke_beranda(): void
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/beranda');
    }

    public function test_hrd_lihat_kartu_pending_cuti(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\JenisCutiSeeder::class);

        $kar = Karyawan::factory()->create();
        $hrd = User::factory()->create(['karyawan_id' => $kar->id]);
        $hrd->assignRole('HRD');

        $pemohon = Karyawan::factory()->create();
        $izin = \App\Models\JenisCuti::where('kode', 'izin_biasa')->first();
        \App\Models\PengajuanCuti::factory()->for($pemohon)->for($izin, 'jenisCuti')->create([
            'tanggal_mulai' => '2026-06-10', 'tanggal_selesai' => '2026-06-10',
            'jumlah_hari' => 1, 'status' => 'diajukan',
        ]);

        $this->actingAs($hrd)->get('/beranda')
            ->assertOk()
            ->assertSee('Pending Cuti');
    }

    public function test_non_hrd_tak_lihat_kartu_pending_cuti(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $kar->id]);
        $u->assignRole('Karyawan');

        $this->actingAs($u)->get('/beranda')->assertDontSee('Pending Cuti');
    }
}
