<?php

namespace Tests\Feature\Cuti;

use App\Enums\OrgUnitTipe;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Models\ApprovalCuti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Support\RantaiApproval;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RantaiApprovalBangunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, JenisCutiSeeder::class]);
    }

    public function test_membangun_baris_approval_terurut_status_menunggu(): void
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $hrd = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(Role::Hrd->value);

        $pengajuan = PengajuanCuti::factory()->for($staff)->create();

        RantaiApproval::bangunUntuk($pengajuan);

        $rows = ApprovalCuti::where('pengajuan_cuti_id', $pengajuan->id)->orderBy('urutan')->get();
        $this->assertSame([$koor->id, $kabid->id, $hrd->id], $rows->pluck('approver_id')->all());
        $this->assertSame([1, 2, 3], $rows->pluck('urutan')->all());
        $this->assertTrue($rows->every(fn ($r) => $r->status === StatusApproval::Menunggu));
        $this->assertSame(PeranApproval::Hrd, $rows->last()->peran);
    }

    public function test_idempoten_membangun_ulang_mengganti_baris_lama(): void
    {
        $unit = OrgUnit::create(['nama' => 'Mandiri', 'tipe' => OrgUnitTipe::Unit->value]);
        $staff = Karyawan::factory()->staffUnit($unit)->create();
        $hrd = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(Role::Hrd->value);
        $pengajuan = PengajuanCuti::factory()->for($staff)->create();

        RantaiApproval::bangunUntuk($pengajuan);
        RantaiApproval::bangunUntuk($pengajuan);

        $this->assertSame(1, ApprovalCuti::where('pengajuan_cuti_id', $pengajuan->id)->count());
    }
}
