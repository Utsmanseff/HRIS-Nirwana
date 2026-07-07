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
use App\Notifications\SanksiDitolak;
use App\Support\ProsesSanksi;
use App\Support\RantaiSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesSanksiTolakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_kabid_tolak_status_ditolak_dan_notif_pengusul(): void
    {
        Notification::fake();
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        User::factory()->create(['karyawan_id' => $direktur->id])->assignRole(Role::Direktur->value);
        $kabidUser = User::factory()->create(['karyawan_id' => $kabid->id]);
        $koorUser = User::factory()->create(['karyawan_id' => $koor->id]);
        $hrd = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(Role::Hrd->value);

        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Teguran1)->create([
            'karyawan_id' => $staff->id, 'pengusul_id' => $koor->id, 'status' => StatusSanksi::Diajukan,
        ]);
        RantaiSanksi::bangunUntuk($sanksi);

        ProsesSanksi::tolak($sanksi->tahapAktif(), $kabidUser, 'Bukti tak memadai.');

        $sanksi->refresh();
        $this->assertSame(StatusSanksi::Ditolak, $sanksi->status);
        $this->assertSame('Bukti tak memadai.', $sanksi->alasan_tolak);
        $step = $sanksi->approval()->where('approver_id', $kabid->id)->first();
        $this->assertSame(StatusApproval::Tolak, $step->status);
        Notification::assertSentTo($koorUser, SanksiDitolak::class);
    }
}
