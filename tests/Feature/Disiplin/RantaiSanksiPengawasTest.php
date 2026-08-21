<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\RutePengawas;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use App\Support\RantaiSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RantaiSanksiPengawasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Direktorat > Penunjang (bidang) > Farmasi (unit), plus unit Pengawas sejajar bidang.
     * Direktur & HRD ber-role supaya rantainya punya penutup.
     */
    private function hierarki(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $unitPengawas = OrgUnit::create(['nama' => 'SPI', 'tipe' => OrgUnitTipe::Bagian->value, 'parent_id' => $dir->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staf = Karyawan::factory()->staffUnit($unit)->create();

        $hrd = Karyawan::factory()->create();
        $this->beriRole($direktur, Role::Direktur);
        $this->beriRole($hrd, Role::Hrd);

        return compact('dir', 'bidang', 'unit', 'unitPengawas', 'direktur', 'kabid', 'koor', 'staf', 'hrd');
    }

    private function beriRole(Karyawan $kar, Role $role): void
    {
        User::factory()->create(['karyawan_id' => $kar->id])->assignRole($role->value);
    }

    private function jadikanPengawas(Karyawan $kar, RutePengawas $rute): Karyawan
    {
        $kar->jabatan->update(['rute_pengawas' => $rute]);

        return $kar->fresh();
    }

    public function test_pengawas_langsung_hrd_melewati_garis_komando_terdakwa(): void
    {
        $h = $this->hierarki();
        $spi = $this->jadikanPengawas(Karyawan::factory()->pimpinanUnit($h['unitPengawas'], 3)->create(), RutePengawas::LangsungHrd);

        $steps = RantaiSanksi::susun($spi, $h['staf']);

        $this->assertSame([$h['hrd']->id, $h['direktur']->id], $steps->pluck('approver.id')->all());
        $this->assertSame([PeranApproval::Hrd, PeranApproval::Direktur], $steps->pluck('peran')->all());
    }

    public function test_rute_langsung_hrd_tidak_ikut_level_jabatan(): void
    {
        // Pengawas berlevel Koordinator: kalau rutenya disimpulkan dari level, dia akan
        // dirutekan lewat atasan. Rute eksplisit harus menang.
        $h = $this->hierarki();
        $spi = $this->jadikanPengawas(Karyawan::factory()->pimpinanUnit($h['unitPengawas'], 2)->create(), RutePengawas::LangsungHrd);

        $steps = RantaiSanksi::susun($spi, $h['staf']);

        $this->assertSame([$h['hrd']->id, $h['direktur']->id], $steps->pluck('approver.id')->all());
    }

    public function test_pengawas_lewat_atasan_masuk_dari_koordinator_unit_terdakwa(): void
    {
        $h = $this->hierarki();
        $unitLain = OrgUnit::create(['nama' => 'Driver', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $h['bidang']->id]);
        $spv = $this->jadikanPengawas(Karyawan::factory()->pimpinanUnit($unitLain, 2)->create(), RutePengawas::LewatAtasan);

        $steps = RantaiSanksi::susun($spv, $h['staf']);

        $this->assertSame(
            [$h['koor']->id, $h['kabid']->id, $h['hrd']->id, $h['direktur']->id],
            $steps->pluck('approver.id')->all()
        );
        $this->assertSame(
            [PeranApproval::Koordinator, PeranApproval::Kabid, PeranApproval::Hrd, PeranApproval::Direktur],
            $steps->pluck('peran')->all()
        );
    }

    public function test_pengawas_lewat_atasan_melewati_dirinya_sendiri_saat_mengusulkan_bawahan(): void
    {
        // Koordinator unit terdakwa kebetulan si pengusul → dilewati, bukan jadi
        // penyetuju usulannya sendiri.
        $h = $this->hierarki();
        $spv = $this->jadikanPengawas($h['koor'], RutePengawas::LewatAtasan);

        $steps = RantaiSanksi::susun($spv, $h['staf']);

        $this->assertSame([$h['kabid']->id, $h['hrd']->id, $h['direktur']->id], $steps->pluck('approver.id')->all());
    }

    public function test_pengawas_lewat_atasan_tanpa_terdakwa_pakai_rantai_sendiri(): void
    {
        // Layar usul menampilkan pratinjau rantai sebelum karyawan dipilih.
        $h = $this->hierarki();
        $spv = $this->jadikanPengawas($h['koor'], RutePengawas::LewatAtasan);

        $steps = RantaiSanksi::susun($spv);

        $this->assertSame([$h['kabid']->id, $h['hrd']->id, $h['direktur']->id], $steps->pluck('approver.id')->all());
    }

    public function test_jabatan_biasa_tidak_terpengaruh_terdakwa(): void
    {
        // Pengusul non-pengawas hanya bisa mengusulkan bawahannya, jadi rantainya tetap
        // dihitung dari dirinya sendiri walau terdakwa ikut dikirim.
        $h = $this->hierarki();

        $steps = RantaiSanksi::susun($h['koor'], $h['staf']);

        $this->assertSame([$h['kabid']->id, $h['hrd']->id, $h['direktur']->id], $steps->pluck('approver.id')->all());
    }
}
