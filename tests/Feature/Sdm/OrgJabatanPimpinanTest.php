<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\OrgStruktur;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class OrgJabatanPimpinanTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_panel_jabatan_menampilkan_jabatan_pimpinan_unit(): void
    {
        $unit = OrgUnit::factory()->bagian()->create(['nama' => 'SPI', 'parent_id' => null]);

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->assertSee('Kabag SPI');

        // Dibuat lazy saat panel dibuka.
        $this->assertDatabaseHas('jabatan', [
            'org_unit_id' => $unit->id, 'level' => 3, 'nama' => 'Kabag SPI',
        ]);
    }

    public function test_rename_jabatan_pimpinan(): void
    {
        $unit = OrgUnit::factory()->bagian()->create(['nama' => 'SPI', 'parent_id' => null]);
        $jab = $unit->jabatanPimpinan();

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->call('editJabatanPimpinan', $jab->id)
            ->assertSet('jpNama', 'Kabag SPI')
            ->set('jpNama', 'Ketua SPI')
            ->call('simpanJabatanPimpinan')
            ->assertHasNoErrors();

        $jab->refresh();
        $this->assertSame('Ketua SPI', $jab->nama);
        $this->assertSame(3, $jab->level->value);
        $this->assertSame($unit->id, $jab->org_unit_id);
    }

    public function test_nama_jabatan_pimpinan_wajib(): void
    {
        $unit = OrgUnit::factory()->create(['parent_id' => null]);
        $jab = $unit->jabatanPimpinan();

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->call('editJabatanPimpinan', $jab->id)
            ->set('jpNama', '')
            ->call('simpanJabatanPimpinan')
            ->assertHasErrors(['jpNama']);
    }

    public function test_simpan_pimpinan_tanpa_pilih_tak_membuat_jabatan_baru(): void
    {
        $unit = OrgUnit::factory()->create(['parent_id' => null]);
        $unit->jabatanPimpinan();
        $sebelum = Jabatan::where('org_unit_id', $unit->id)->count();

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->set('jpNama', 'Jabatan Selundupan')
            ->call('simpanJabatanPimpinan');

        $this->assertSame($sebelum, Jabatan::where('org_unit_id', $unit->id)->count());
        $this->assertDatabaseMissing('jabatan', ['nama' => 'Jabatan Selundupan']);
    }

    public function test_jabatan_pimpinan_tak_bisa_diedit_lewat_jalur_staff(): void
    {
        $unit = OrgUnit::factory()->create(['parent_id' => null]);
        $jab = $unit->jabatanPimpinan();

        Livewire::actingAs($this->userSdm())->test(OrgStruktur::class)
            ->call('bukaJabatan', $unit->id)
            ->call('editJabatanStaff', $jab->id)
            ->set('jNama', 'Diturunkan')
            ->call('simpanJabatanStaff');

        $jab->refresh();
        $this->assertSame(2, $jab->level->value);
        $this->assertNotSame('Diturunkan', $jab->nama);
    }

    public function test_rename_unit_ikut_menyegarkan_nama_jabatan_pimpinan_default(): void
    {
        $unit = OrgUnit::factory()->bagian()->create(['nama' => 'Keuangan', 'parent_id' => null]);
        $jab = $unit->jabatanPimpinan();
        $this->assertSame('Kabag Keuangan', $jab->nama);

        $unit->update(['nama' => 'Keuangan & Akuntansi']);

        $this->assertSame('Kabag Keuangan & Akuntansi', $jab->refresh()->nama);
    }

    public function test_rename_unit_tak_menimpa_jabatan_pimpinan_yang_sudah_diubah_manual(): void
    {
        $unit = OrgUnit::factory()->bagian()->create(['nama' => 'SPI', 'parent_id' => null]);
        $jab = $unit->jabatanPimpinan();
        $jab->update(['nama' => 'Ketua SPI']);

        $unit->update(['nama' => 'Satuan Pengawas Internal']);

        $this->assertSame('Ketua SPI', $jab->refresh()->nama);
    }

    public function test_rename_unit_ikut_menyegarkan_jabatan_staff_default(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Farmasi', 'parent_id' => null]);
        $staff = $unit->jabatanStaffDefault();
        $this->assertSame('Staff Farmasi', $staff->nama);

        $unit->update(['nama' => 'Farmasi Klinis']);

        $this->assertSame('Staff Farmasi Klinis', $staff->refresh()->nama);
        // Tak ada duplikat saat dipanggil lagi.
        $this->assertSame($staff->id, $unit->refresh()->jabatanStaffDefault()->id);
        $this->assertSame(1, Jabatan::where('org_unit_id', $unit->id)->where('level', 1)->count());
    }

    public function test_rename_unit_tak_menimpa_jabatan_staff_yang_diubah_manual(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Farmasi', 'parent_id' => null]);
        $staff = $unit->jabatanStaffDefault();
        $staff->update(['nama' => 'Asisten Apoteker']);

        $unit->update(['nama' => 'Farmasi Klinis']);

        $this->assertSame('Asisten Apoteker', $staff->refresh()->nama);
    }
}
