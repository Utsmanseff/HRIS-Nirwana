<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\KaryawanIndex;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\OrgUnit;
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

    public function test_search_nama_atau_nip(): void
    {
        Karyawan::factory()->create(['nama_lengkap' => 'Budi Santoso', 'nip' => '2025.07.0233']);
        Karyawan::factory()->create(['nama_lengkap' => 'Dewi Kartika', 'nip' => '2025.11.0341']);

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->set('cari', 'Budi')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Dewi Kartika');

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->set('cari', '2025.11')
            ->assertSee('Dewi Kartika')
            ->assertDontSee('Budi Santoso');
    }

    public function test_filter_unit_termasuk_turunan(): void
    {
        $bidang = OrgUnit::factory()->create(['nama' => 'Penunjang', 'tipe' => 'bidang', 'parent_id' => null]);
        $divisi = OrgUnit::factory()->create(['nama' => 'Divisi IT', 'tipe' => 'divisi', 'parent_id' => $bidang->id]);
        $lain = OrgUnit::factory()->create(['nama' => 'Bidang Lain', 'tipe' => 'bidang', 'parent_id' => null]);
        Karyawan::factory()->create(['nama_lengkap' => 'Anak Divisi', 'org_unit_id' => $divisi->id]);
        Karyawan::factory()->create(['nama_lengkap' => 'Orang Lain', 'org_unit_id' => $lain->id]);

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->set('unitId', (string) $bidang->id)
            ->assertSee('Anak Divisi')
            ->assertDontSee('Orang Lain');
    }

    public function test_filter_level_jabatan(): void
    {
        $staff = Jabatan::factory()->create(['nama' => 'Staff Lab', 'level' => 1]);
        $koor = Jabatan::factory()->create(['nama' => 'Koordinator Lab', 'level' => 2]);
        Karyawan::factory()->create(['nama_lengkap' => 'Si Staff', 'jabatan_id' => $staff->id]);
        Karyawan::factory()->create(['nama_lengkap' => 'Si Koordinator', 'jabatan_id' => $koor->id]);

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->set('level', '2')
            ->assertSee('Si Koordinator')
            ->assertDontSee('Si Staff');
    }

    public function test_filter_jenis_kontrak_terbaru(): void
    {
        $tetap = Karyawan::factory()->create(['nama_lengkap' => 'Karyawan Tetap']);
        Kontrak::factory()->create(['karyawan_id' => $tetap->id, 'jenis' => 'pkwt', 'tanggal_mulai' => '2023-01-01', 'tanggal_akhir' => '2023-12-31']);
        Kontrak::factory()->create(['karyawan_id' => $tetap->id, 'jenis' => 'tetap', 'tanggal_mulai' => '2024-01-01', 'tanggal_akhir' => null]);
        $pkwt = Karyawan::factory()->create(['nama_lengkap' => 'Karyawan Pkwt']);
        Kontrak::factory()->create(['karyawan_id' => $pkwt->id, 'jenis' => 'pkwt', 'tanggal_mulai' => '2024-01-01', 'tanggal_akhir' => '2027-06-30']);

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->set('kontrakJenis', 'tetap')
            ->assertSee('Karyawan Tetap')
            ->assertDontSee('Karyawan Pkwt');
    }

    public function test_default_hanya_aktif_dan_toggle_semua(): void
    {
        Karyawan::factory()->create(['nama_lengkap' => 'Masih Aktif', 'status' => 'aktif']);
        Karyawan::factory()->create(['nama_lengkap' => 'Sudah Nonaktif', 'status' => 'nonaktif', 'alasan_nonaktif' => 'resign', 'tanggal_nonaktif' => '2026-01-01']);

        Livewire::actingAs($this->userSdm())->test(KaryawanIndex::class)
            ->assertSee('Masih Aktif')
            ->assertDontSee('Sudah Nonaktif')
            ->set('status', 'semua')
            ->assertSee('Masih Aktif')
            ->assertSee('Sudah Nonaktif');
    }
}
