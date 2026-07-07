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

    /** Rantai [Kabid, HRD] dari usulan koordinator. */
    protected function skenario(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);

        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $kabidUser = User::factory()->create(['karyawan_id' => $kabid->id]);

        $hrd = Karyawan::factory()->create();
        $hrdUser = User::factory()->create(['karyawan_id' => $hrd->id]);
        $hrdUser->assignRole(Role::Hrd->value);

        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Teguran1)->create([
            'karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan,
        ]);
        RantaiSanksi::bangunUntuk($sanksi);

        return compact('kabid', 'kabidUser', 'hrd', 'hrdUser', 'sanksi');
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
        // Tahap aktif kini HRD.
        $this->assertSame($s['hrd']->id, $s['sanksi']->tahapAktif()->approver_id);
        Notification::assertSentTo($s['hrdUser'], SanksiPerluPersetujuan::class);
    }

    public function test_setujui_tahap_final_ditolak_harus_terbit(): void
    {
        $s = $this->skenario();
        // Majukan ke HRD dulu.
        ProsesSanksi::setujui($s['sanksi']->tahapAktif(), $s['kabidUser']);
        $stepHrd = $s['sanksi']->fresh()->tahapAktif();

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::setujui($stepHrd, $s['hrdUser']); // final → harus pakai terbit
    }

    public function test_setujui_bukan_approver_ditolak(): void
    {
        $s = $this->skenario();
        $orangLain = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::setujui($s['sanksi']->tahapAktif(), $orangLain);
    }
}
