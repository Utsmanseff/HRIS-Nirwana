<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\KaryawanForm;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class KaryawanFormTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_halaman_tambah_terbuka(): void
    {
        $this->actingAs($this->userSdm())->get('/sdm/karyawan/tambah')->assertOk();
    }

    public function test_tambah_karyawan_beserta_kontrak_awal(): void
    {
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->set('nip', '2026.07.0001')
            ->set('namaLengkap', 'Andi Pratama')
            ->set('jabatanId', (string) $jab->id)
            ->set('tanggalMasuk', '2026-07-01')
            ->set('jenisKontrak', 'percobaan_unpaid')
            ->set('kontrakMulai', '2026-07-01')
            ->set('kontrakAkhir', '2026-07-14')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('karyawan', ['nip' => '2026.07.0001', 'nama_lengkap' => 'Andi Pratama', 'status' => 'aktif']);
        $kar = Karyawan::where('nip', '2026.07.0001')->first();
        $this->assertDatabaseHas('kontrak', ['karyawan_id' => $kar->id, 'jenis' => 'percobaan_unpaid', 'tanggal_akhir' => '2026-07-14 00:00:00']);
    }

    public function test_nip_wajib_unik(): void
    {
        Karyawan::factory()->create(['nip' => 'DUP-001']);
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->set('nip', 'DUP-001')
            ->set('namaLengkap', 'Siapa Saja')
            ->set('jabatanId', (string) $jab->id)
            ->set('tanggalMasuk', '2026-07-01')
            ->set('jenisKontrak', 'tetap')
            ->set('kontrakMulai', '2026-07-01')
            ->call('simpan')
            ->assertHasErrors(['nip']);
    }

    public function test_kontrak_berbatas_wajib_tanggal_akhir(): void
    {
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->set('nip', '2026.07.0002')
            ->set('namaLengkap', 'Budi')
            ->set('jabatanId', (string) $jab->id)
            ->set('tanggalMasuk', '2026-07-01')
            ->set('jenisKontrak', 'pkwt')
            ->set('kontrakMulai', '2026-07-01')
            ->set('kontrakAkhir', '')
            ->call('simpan')
            ->assertHasErrors(['kontrakAkhir']);
    }

    public function test_kontrak_tetap_tanggal_akhir_diabaikan(): void
    {
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->set('nip', '2026.07.0003')
            ->set('namaLengkap', 'Citra')
            ->set('jabatanId', (string) $jab->id)
            ->set('tanggalMasuk', '2026-07-01')
            ->set('jenisKontrak', 'tetap')
            ->set('kontrakMulai', '2026-07-01')
            ->set('kontrakAkhir', '2027-01-01')
            ->call('simpan')
            ->assertHasNoErrors();

        $kar = Karyawan::where('nip', '2026.07.0003')->first();
        $this->assertDatabaseHas('kontrak', ['karyawan_id' => $kar->id, 'jenis' => 'tetap', 'tanggal_akhir' => null]);
    }

    public function test_form_ubah_terisi_dan_update(): void
    {
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Nama Lama', 'nip' => 'TETAP-01']);

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class, ['karyawan' => $kar])
            ->assertSet('namaLengkap', 'Nama Lama')
            ->assertSet('nip', 'TETAP-01')
            ->set('namaLengkap', 'Nama Baru')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('karyawan', ['id' => $kar->id, 'nama_lengkap' => 'Nama Baru', 'nip' => 'TETAP-01']);
    }

    public function test_ubah_nip_unik_abaikan_diri_sendiri(): void
    {
        $kar = Karyawan::factory()->create(['nip' => 'SAYA-01']);

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class, ['karyawan' => $kar])
            ->call('simpan')
            ->assertHasNoErrors();
    }

    public function test_ubah_tidak_menambah_kontrak(): void
    {
        $kar = Karyawan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class, ['karyawan' => $kar])
            ->set('namaLengkap', 'Ganti Nama')
            ->call('simpan');

        $this->assertSame(0, $kar->kontrak()->count());
    }

    public function test_pilih_jabatan_set_id_dan_label(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Farmasi']);
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'nama' => 'Apoteker', 'level' => 1]);

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->call('pilihJabatan', $jab->id)
            ->assertSet('jabatanId', (string) $jab->id)
            ->assertSee('Apoteker')
            ->assertSee('Farmasi');
    }

    public function test_org_unit_auto_dari_jabatan_saat_simpan(): void
    {
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id]);

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->set('nip', 'AUTO-1')
            ->set('namaLengkap', 'Auto Unit')
            ->call('pilihJabatan', $jab->id)
            ->set('tanggalMasuk', '2026-07-01')
            ->set('jenisKontrak', 'tetap')
            ->set('kontrakMulai', '2026-07-01')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('karyawan', [
            'nip' => 'AUTO-1', 'org_unit_id' => $unit->id, 'jabatan_id' => $jab->id,
        ]);
    }

    public function test_cari_jabatan_menampilkan_hasil_by_unit(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Radiologi']);
        Jabatan::factory()->create(['org_unit_id' => $unit->id, 'nama' => 'Radiografer', 'level' => 1]);

        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->set('cariJabatan', 'Radiolog')
            ->assertSee('Radiografer');
    }

    public function test_jabatan_wajib(): void
    {
        Livewire::actingAs($this->userSdm())->test(KaryawanForm::class)
            ->set('nip', 'NOJAB-1')
            ->set('namaLengkap', 'Tanpa Jabatan')
            ->set('tanggalMasuk', '2026-07-01')
            ->set('jenisKontrak', 'tetap')
            ->set('kontrakMulai', '2026-07-01')
            ->call('simpan')
            ->assertHasErrors(['jabatanId']);
    }
}
