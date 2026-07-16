<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Support\ProsesSanksi;
use App\Support\RantaiSanksi;
use App\Support\TandaTanganQR;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifikasiSanksiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Notification::fake();
    }

    /** Bangun sanksi diterbitkan (pengusul Koordinator → rantai Kabid/HRD/Direktur). */
    protected function sanksiTerbit(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $userDir = User::factory()->create(['karyawan_id' => $direktur->id]);
        $userDir->assignRole(Role::Direktur->value);
        $userKabid = User::factory()->create(['karyawan_id' => $kabid->id]);

        // HRD harus ada SEBELUM bangunUntuk — rantai koor menyertakan tahap HRD.
        $hrdKar = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $userHrd = User::factory()->create(['karyawan_id' => $hrdKar->id]);
        $userHrd->assignRole(Role::Hrd->value);

        $s = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Sp1)->create([
            'karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan,
        ]);
        RantaiSanksi::bangunUntuk($s);

        // Kabid setujui → HRD setujui (isi nomor) → Direktur terbit.
        $kabidStep = $s->approval()->where('peran', \App\Enums\PeranApproval::Kabid->value)->first();
        ProsesSanksi::setujui($kabidStep, $userKabid);
        $s->refresh();
        $hrdStep = $s->approval()->where('peran', \App\Enums\PeranApproval::Hrd->value)->first();
        ProsesSanksi::setujui($hrdStep, $userHrd, nomor: 'SP-01/RSUN/2026');
        $s->refresh();
        $dirStep = $s->approval()->where('peran', \App\Enums\PeranApproval::Direktur->value)->first();
        ProsesSanksi::terbit($dirStep, $userDir);

        return ['sanksi' => $s->fresh(), 'staff' => $staff, 'koor' => $koor];
    }

    public function test_url_signed_penerbit_menampilkan_detail_tanpa_login(): void
    {
        $d = $this->sanksiTerbit();
        $url = TandaTanganQR::url($d['sanksi'], 'penerbit');

        $this->get($url)
            ->assertOk()
            ->assertSee('SP-01/RSUN/2026')
            ->assertSee($d['staff']->nama_lengkap)
            ->assertSee('Direktur');
    }

    public function test_pengusul_menampilkan_label_koordinator(): void
    {
        $d = $this->sanksiTerbit();
        $this->get(TandaTanganQR::url($d['sanksi'], 'pengusul'))
            ->assertOk()
            ->assertSee($d['koor']->nama_lengkap)
            ->assertSee('Koordinator');
    }

    public function test_signature_rusak_ditolak(): void
    {
        $d = $this->sanksiTerbit();
        $url = TandaTanganQR::url($d['sanksi'], 'penerbit').'x'; // rusak signature

        $this->get($url)->assertStatus(403)->assertSee('tidak dikenali', false);
    }

    public function test_status_dicabut_tampil(): void
    {
        $d = $this->sanksiTerbit();
        $admin = User::factory()->create();
        $admin->assignRole(Role::Hrd->value);
        ProsesSanksi::cabut($d['sanksi'], $admin, 'Pembinaan selesai');

        $this->get(TandaTanganQR::url($d['sanksi']->fresh(), 'penerbit'))
            ->assertOk()
            ->assertSee('DICABUT')
            ->assertSee('Pembinaan selesai');
    }

    public function test_uraian_pelanggaran_tidak_bocor(): void
    {
        $d = $this->sanksiTerbit();
        $uraian = $d['sanksi']->uraian;

        $this->get(TandaTanganQR::url($d['sanksi'], 'penerbit'))
            ->assertOk()
            ->assertDontSee($uraian);
    }
}
