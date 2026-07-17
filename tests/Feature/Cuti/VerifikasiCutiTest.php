<?php

namespace Tests\Feature\Cuti;

use App\Enums\OrgUnitTipe;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Support\ProsesApproval;
use App\Support\RantaiApproval;
use App\Support\TandaTanganQR;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, JenisCutiSeeder::class]);
    }

    /** Bangun pengajuan cuti dengan rantai koor→kabid→hrd, lalu approve semua tahap. */
    protected function cutiDisetujui(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $hrdKar = Karyawan::factory()->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrdKar->id]);
        $userHrd->assignRole(Role::Hrd->value);
        $userKoor = User::factory()->create(['karyawan_id' => $koor->id]);
        $userKabid = User::factory()->create(['karyawan_id' => $kabid->id]);

        $pengajuan = PengajuanCuti::factory()->for($staff)->jenis(\App\Enums\KodeJenisCuti::CutiSakit)->create();
        RantaiApproval::bangunUntuk($pengajuan);

        $koorStep = $pengajuan->approval()->where('peran', PeranApproval::Koordinator->value)->first();
        ProsesApproval::setujui($koorStep, $userKoor);
        $pengajuan->refresh();
        $kabidStep = $pengajuan->approval()->where('peran', PeranApproval::Kabid->value)->first();
        ProsesApproval::setujui($kabidStep, $userKabid);
        $pengajuan->refresh();
        $hrdStep = $pengajuan->approval()->where('peran', PeranApproval::Hrd->value)->first();
        ProsesApproval::setujui($hrdStep, $userHrd);

        return ['pengajuan' => $pengajuan->fresh(), 'staff' => $staff, 'koor' => $koor, 'kabid' => $kabid];
    }

    public function test_url_signed_pemohon_menampilkan_detail_tanpa_login(): void
    {
        $d = $this->cutiDisetujui();
        $url = TandaTanganQR::urlCuti($d['pengajuan'], 'pemohon');

        $this->get($url)
            ->assertOk()
            ->assertSee($d['staff']->nama_lengkap)
            ->assertSee('Pemohon')
            ->assertSee('DISETUJUI');
    }

    public function test_sumber_koordinator_menampilkan_nama_dan_peran_benar(): void
    {
        $d = $this->cutiDisetujui();
        $this->get(TandaTanganQR::urlCuti($d['pengajuan'], 'koordinator'))
            ->assertOk()
            ->assertSee($d['koor']->nama_lengkap)
            ->assertSee('Koordinator');
    }

    public function test_sumber_kabid_menampilkan_nama_dan_peran_benar(): void
    {
        $d = $this->cutiDisetujui();
        $this->get(TandaTanganQR::urlCuti($d['pengajuan'], 'kabid'))
            ->assertOk()
            ->assertSee($d['kabid']->nama_lengkap)
            ->assertSee('Kabid');
    }

    public function test_signature_rusak_ditolak(): void
    {
        $d = $this->cutiDisetujui();
        $url = TandaTanganQR::urlCuti($d['pengajuan'], 'pemohon').'x';

        $this->get($url)->assertStatus(403);
    }

    public function test_jenis_cuti_tidak_ditampilkan(): void
    {
        $d = $this->cutiDisetujui();

        $this->get(TandaTanganQR::urlCuti($d['pengajuan'], 'pemohon'))
            ->assertOk()
            ->assertDontSee('Jenis Cuti')
            ->assertDontSee($d['pengajuan']->jenisCuti->nama);
    }

    /** Label peran dari enum, bukan ucfirst($sumber) yang bikin "Hrd". */
    public function test_sumber_hrd_berlabel_hrd(): void
    {
        $d = $this->cutiDisetujui();

        $this->get(TandaTanganQR::urlCuti($d['pengajuan'], 'hrd'))
            ->assertOk()
            ->assertSee('HRD')
            ->assertDontSee('Hrd');
    }
}
