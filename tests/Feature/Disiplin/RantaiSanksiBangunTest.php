<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Support\RantaiSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RantaiSanksiBangunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_bangun_persist_baris_menunggu_terurut(): void
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        User::factory()->create(['karyawan_id' => $direktur->id])->assignRole(Role::Direktur->value);
        $hrd = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $hrd->id]);
        $u->assignRole(Role::Hrd->value);

        $kena = Karyawan::factory()->staffUnit($unit)->create();
        $sanksi = SanksiDisiplin::factory()->create([
            'karyawan_id' => $kena->id,
            'pengusul_id' => $koor->id,
        ]);

        RantaiSanksi::bangunUntuk($sanksi);

        $baris = $sanksi->approval()->get();
        $this->assertSame([$kabid->id, $hrd->id, $direktur->id], $baris->pluck('approver_id')->all());
        $this->assertSame([1, 2, 3], $baris->pluck('urutan')->all());
        $this->assertTrue($baris->every(fn ($b) => $b->status === StatusApproval::Menunggu));
        $this->assertSame(PeranApproval::Direktur, $baris->last()->peran);
    }

    public function test_bangun_idempoten_ganti_baris_lama(): void
    {
        $unit = OrgUnit::create(['nama' => 'Mandiri', 'tipe' => OrgUnitTipe::Unit->value]);
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $direktur = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $direktur->id])->assignRole(Role::Direktur->value);
        $hrd = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $hrd->id]);
        $u->assignRole(Role::Hrd->value);
        $sanksi = SanksiDisiplin::factory()->create(['pengusul_id' => $koor->id]);

        RantaiSanksi::bangunUntuk($sanksi);
        RantaiSanksi::bangunUntuk($sanksi);

        // koor→HRD→Direktur (koor tak punya kabid induk) = 2 baris, tak dobel.
        $this->assertSame(2, $sanksi->approval()->count());
    }
}
