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
use App\Support\RantaiSanksi;
use App\Support\SuratSanksi;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratSanksiPenandatanganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Direktorat > Bidang > Unit + kepala tiap tingkat + HRD (role). */
    protected function hierarki(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();

        $hrd = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(Role::Hrd->value);

        return compact('dir', 'bidang', 'unit', 'direktur', 'kabid', 'koor', 'staff', 'hrd');
    }

    protected function sanksiDari(Karyawan $pengusul, Karyawan $kena): SanksiDisiplin
    {
        $s = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Teguran1)->create([
            'karyawan_id' => $kena->id, 'pengusul_id' => $pengusul->id, 'status' => StatusSanksi::Diajukan,
        ]);
        RantaiSanksi::bangunUntuk($s);

        return $s;
    }

    public function test_pengusul_koordinator_tiga_ttd(): void
    {
        $h = $this->hierarki();
        $ttd = SuratSanksi::penandatangan($this->sanksiDari($h['koor'], $h['staff']));

        $this->assertCount(3, $ttd);
        $this->assertSame(['Pengusul', 'Kabid', 'HRD'], array_column($ttd, 'peran'));
        $this->assertSame($h['koor']->nama_lengkap, $ttd[0]['nama']);
    }

    public function test_pengusul_kabid_dua_ttd(): void
    {
        $h = $this->hierarki();
        $ttd = SuratSanksi::penandatangan($this->sanksiDari($h['kabid'], $h['staff']));

        $this->assertCount(2, $ttd);
        $this->assertSame(['Pengusul', 'HRD'], array_column($ttd, 'peran'));
    }

    public function test_pengusul_hrd_satu_ttd(): void
    {
        $h = $this->hierarki();
        $ttd = SuratSanksi::penandatangan($this->sanksiDari($h['hrd'], $h['staff']));

        $this->assertCount(1, $ttd);
        $this->assertSame(['HRD'], array_column($ttd, 'peran'));
        $this->assertSame($h['hrd']->nama_lengkap, $ttd[0]['nama']);
    }

    public function test_pengusul_direktur_satu_ttd(): void
    {
        $h = $this->hierarki();
        $ttd = SuratSanksi::penandatangan($this->sanksiDari($h['direktur'], $h['staff']));

        $this->assertCount(1, $ttd);
        $this->assertSame(['Direktur'], array_column($ttd, 'peran'));
        $this->assertSame($h['direktur']->nama_lengkap, $ttd[0]['nama']);
    }
}
