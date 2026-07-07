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

    /** Rantai [Kabid, HRD, Direktur]. Majukan sampai tahap aktif = Direktur (HRD sudah isi nomor). */
    protected function siapTerbit(): array
    {
        Storage::fake('local');
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
        $staffUser = User::factory()->create(['karyawan_id' => $staff->id]);
        $hrd = Karyawan::factory()->create();
        $hrdUser = User::factory()->create(['karyawan_id' => $hrd->id]);
        $hrdUser->assignRole(Role::Hrd->value);

        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Teguran2)->create([
            'karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan,
        ]);
        RantaiSanksi::bangunUntuk($sanksi);
        ProsesSanksi::setujui($sanksi->tahapAktif(), $kabidUser); // Kabid → HRD
        ProsesSanksi::setujui($sanksi->fresh()->tahapAktif(), $hrdUser, null, null, '01.200/HRD/RSUN/VII/2026'); // HRD isi nomor → Direktur

        return compact('sanksi', 'direkturUser', 'staffUser');
    }

    public function test_direktur_terbit_pakai_nomor_dari_hrd(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-07-08');
        $s = $this->siapTerbit();
        $stepDir = $s['sanksi']->fresh()->tahapAktif();

        ProsesSanksi::terbit($stepDir, $s['direkturUser'], null, 'Diterbitkan.');

        $sanksi = $s['sanksi']->fresh();
        $this->assertSame(StatusSanksi::Diterbitkan, $sanksi->status);
        $this->assertSame('01.200/HRD/RSUN/VII/2026', $sanksi->nomor_surat);
        $this->assertSame('2026-07-08', $sanksi->tanggal_terbit->toDateString());
        $this->assertSame('2027-01-08', $sanksi->berlaku_sampai->toDateString());
        $this->assertSame($s['direkturUser']->id, $sanksi->diterbitkan_oleh);
        $this->assertNotNull($sanksi->surat_path);
        Storage::disk('local')->assertExists($sanksi->surat_path);
        Notification::assertSentTo($s['staffUser'], SanksiDiterbitkan::class);
    }

    public function test_terbit_tanpa_nomor_dan_belum_diisi_ditolak(): void
    {
        // Direktur buat-langsung: rantai [Direktur self], nomor belum ada → terbit tanpa nomor gagal.
        Storage::fake('local');
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $staff = Karyawan::factory()->create();
        $direkturUser = User::factory()->create(['karyawan_id' => $direktur->id]);
        $direkturUser->assignRole(Role::Direktur->value);

        $sanksi = SanksiDisiplin::factory()->create(['karyawan_id' => $staff->id, 'pengusul_id' => $direktur->id, 'status' => StatusSanksi::Diajukan]);
        RantaiSanksi::bangunUntuk($sanksi); // [Direktur self]

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::terbit($sanksi->tahapAktif(), $direkturUser); // tak ada nomor
    }

    public function test_direktur_buat_langsung_terbit_dengan_nomor(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-07-08');
        Storage::fake('local');
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $staff = Karyawan::factory()->create();
        $direkturUser = User::factory()->create(['karyawan_id' => $direktur->id]);
        $direkturUser->assignRole(Role::Direktur->value);
        $staffUser = User::factory()->create(['karyawan_id' => $staff->id]);

        $sanksi = SanksiDisiplin::factory()->create(['karyawan_id' => $staff->id, 'pengusul_id' => $direktur->id, 'status' => StatusSanksi::Diajukan]);
        RantaiSanksi::bangunUntuk($sanksi);

        ProsesSanksi::terbit($sanksi->tahapAktif(), $direkturUser, '01.300/DIR/RSUN/VII/2026');

        $sanksi->refresh();
        $this->assertSame(StatusSanksi::Diterbitkan, $sanksi->status);
        $this->assertSame('01.300/DIR/RSUN/VII/2026', $sanksi->nomor_surat);
        Notification::assertSentTo($staffUser, SanksiDiterbitkan::class);
    }

    public function test_terbit_bukan_tahap_final_ditolak(): void
    {
        Storage::fake('local');
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $kabidUser = User::factory()->create(['karyawan_id' => $kabid->id]);
        User::factory()->create(['karyawan_id' => $direktur->id])->assignRole(Role::Direktur->value);
        $hrd = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(Role::Hrd->value);

        $sanksi = SanksiDisiplin::factory()->create(['karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan]);
        RantaiSanksi::bangunUntuk($sanksi); // tahap aktif = Kabid (bukan final)

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::terbit($sanksi->tahapAktif(), $kabidUser, '01.111/HRD/RSUN/VII/2026');
    }
}
