<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Notifications\SanksiPerluPersetujuan;
use App\Support\ProsesSanksi;
use App\Support\ProsesSanksiException;
use App\Support\RantaiSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesSanksiSetujuiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Rantai [Kabid, HRD, Direktur] dari usulan koordinator. */
    protected function skenario(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();

        $kabidUser = User::factory()->create(['karyawan_id' => $kabid->id]);
        $direkturUser = User::factory()->create(['karyawan_id' => $direktur->id]);
        $direkturUser->assignRole(Role::Direktur->value);

        $hrd = Karyawan::factory()->create();
        $hrdUser = User::factory()->create(['karyawan_id' => $hrd->id]);
        $hrdUser->assignRole(Role::Hrd->value);

        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Teguran1)->create([
            'karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan,
        ]);
        RantaiSanksi::bangunUntuk($sanksi);

        return compact('direktur', 'direkturUser', 'kabid', 'kabidUser', 'hrd', 'hrdUser', 'sanksi');
    }

    public function test_kabid_setujui_maju_ke_diproses_dan_notif_hrd(): void
    {
        Notification::fake();
        $s = $this->skenario();
        $step = $s['sanksi']->tahapAktif(); // Kabid (urutan 1)

        ProsesSanksi::setujui($step, $s['kabidUser'], 'Setuju, bukti cukup.');

        $s['sanksi']->refresh();
        $this->assertSame(StatusSanksi::Diproses, $s['sanksi']->status);
        $step->refresh();
        $this->assertSame(StatusApproval::Setuju, $step->status);
        $this->assertNotNull($step->acted_at);
        $this->assertSame($s['hrd']->id, $s['sanksi']->tahapAktif()->approver_id); // kini HRD
        Notification::assertSentTo($s['hrdUser'], SanksiPerluPersetujuan::class);
    }

    public function test_hrd_setujui_wajib_nomor_lalu_maju_ke_direktur(): void
    {
        Notification::fake();
        $s = $this->skenario();
        ProsesSanksi::setujui($s['sanksi']->tahapAktif(), $s['kabidUser']); // Kabid acc → HRD
        $stepHrd = $s['sanksi']->fresh()->tahapAktif();

        ProsesSanksi::setujui($stepHrd, $s['hrdUser'], null, null, '01.200/HRD/RSUN/VII/2026');

        $sanksi = $s['sanksi']->fresh();
        $this->assertSame('01.200/HRD/RSUN/VII/2026', $sanksi->nomor_surat);
        $this->assertSame($s['direktur']->id, $sanksi->tahapAktif()->approver_id); // kini Direktur
        Notification::assertSentTo($s['direkturUser'], SanksiPerluPersetujuan::class);
    }

    public function test_hrd_setujui_tanpa_nomor_ditolak(): void
    {
        $s = $this->skenario();
        ProsesSanksi::setujui($s['sanksi']->tahapAktif(), $s['kabidUser']); // → HRD
        $stepHrd = $s['sanksi']->fresh()->tahapAktif();

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::setujui($stepHrd, $s['hrdUser']); // tahap HRD tanpa nomor
    }

    public function test_kabid_override_tingkat_saat_setujui(): void
    {
        $s = $this->skenario();
        $step = $s['sanksi']->tahapAktif();

        ProsesSanksi::setujui($step, $s['kabidUser'], 'Naikkan.', TingkatSanksi::Sp1->value);

        $this->assertSame(TingkatSanksi::Sp1, $s['sanksi']->fresh()->tingkat);
    }

    public function test_setujui_tahap_final_direktur_ditolak_harus_terbit(): void
    {
        $s = $this->skenario();
        ProsesSanksi::setujui($s['sanksi']->tahapAktif(), $s['kabidUser']); // → HRD
        ProsesSanksi::setujui($s['sanksi']->fresh()->tahapAktif(), $s['hrdUser'], null, null, '01.201/HRD/RSUN/VII/2026'); // → Direktur
        $stepDir = $s['sanksi']->fresh()->tahapAktif();

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::setujui($stepDir, $s['direkturUser']); // final → harus terbit
    }

    public function test_setujui_bukan_approver_ditolak(): void
    {
        $s = $this->skenario();
        $orangLain = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::setujui($s['sanksi']->tahapAktif(), $orangLain);
    }
}
