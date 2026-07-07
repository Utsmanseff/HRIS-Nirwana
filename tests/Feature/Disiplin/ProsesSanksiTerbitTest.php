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
use App\Notifications\SanksiDiterbitkan;
use App\Support\ProsesSanksi;
use App\Support\ProsesSanksiException;
use App\Support\RantaiSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProsesSanksiTerbitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Kabid sudah setujui → tahap aktif = HRD (final). Kembalikan sanksi + user HRD + karyawan kena. */
    protected function siapTerbit(): array
    {
        Storage::fake('local');
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $kabidUser = User::factory()->create(['karyawan_id' => $kabid->id]);
        $hrd = Karyawan::factory()->create();
        $staffUser = User::factory()->create(['karyawan_id' => $staff->id]);
        $hrdUser = User::factory()->create(['karyawan_id' => $hrd->id]);
        $hrdUser->assignRole(Role::Hrd->value);

        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Teguran2)->create([
            'karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan,
        ]);
        RantaiSanksi::bangunUntuk($sanksi);
        ProsesSanksi::setujui($sanksi->tahapAktif(), $kabidUser); // Kabid acc → tahap aktif HRD

        return compact('sanksi', 'hrdUser', 'staffUser');
    }

    public function test_hrd_terbit_set_status_nomor_tanggal_surat_dan_notif(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-07-07');
        $s = $this->siapTerbit();
        $stepHrd = $s['sanksi']->fresh()->tahapAktif();

        ProsesSanksi::terbit($stepHrd, $s['hrdUser'], '01.200/HRD/RSUN/VII/2026', 'Diterbitkan.');

        $sanksi = $s['sanksi']->fresh();
        $this->assertSame(StatusSanksi::Diterbitkan, $sanksi->status);
        $this->assertSame('01.200/HRD/RSUN/VII/2026', $sanksi->nomor_surat);
        $this->assertSame('2026-07-07', $sanksi->tanggal_terbit->toDateString());
        $this->assertSame('2027-01-07', $sanksi->berlaku_sampai->toDateString());
        $this->assertSame($s['hrdUser']->id, $sanksi->diterbitkan_oleh);
        $this->assertNotNull($sanksi->surat_path);
        Storage::disk('local')->assertExists($sanksi->surat_path);
        Notification::assertSentTo($s['staffUser'], SanksiDiterbitkan::class);
    }

    public function test_nomor_duplikat_ditolak(): void
    {
        $s = $this->siapTerbit();
        SanksiDisiplin::factory()->diterbitkan()->create(['nomor_surat' => '01.999/HRD/RSUN/VII/2026']);
        $stepHrd = $s['sanksi']->fresh()->tahapAktif();

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::terbit($stepHrd, $s['hrdUser'], '01.999/HRD/RSUN/VII/2026');
    }

    public function test_terbit_bukan_tahap_final_ditolak(): void
    {
        // Rantai [Kabid, HRD], tahap aktif masih Kabid → terbit di step Kabid harus gagal.
        Storage::fake('local');
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $kabidUser = User::factory()->create(['karyawan_id' => $kabid->id]);
        User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id])->assignRole(Role::Hrd->value);
        $sanksi = SanksiDisiplin::factory()->create(['karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan]);
        RantaiSanksi::bangunUntuk($sanksi);

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::terbit($sanksi->tahapAktif(), $kabidUser, '01.111/HRD/RSUN/VII/2026');
    }
}
