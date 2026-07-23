<?php

namespace Tests\Feature\Absensi;

use App\Enums\OrgUnitTipe;
use App\Exports\AbsensiExport;
use App\Exports\AbsensiPerUnitExport;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class GateRekapPemimpinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_koordinator_lolos_gate_karyawan_biasa_tidak(): void
    {
        $unit = OrgUnit::factory()->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit)->create();
        $staff = Karyawan::factory()->staffUnit($unit)->create();

        $userKoor = User::factory()->create(['karyawan_id' => $koor->id]);
        $userStaff = User::factory()->create(['karyawan_id' => $staff->id]);

        $this->assertTrue($userKoor->can('lihat-rekap-absensi'));
        $this->assertFalse($userStaff->can('lihat-rekap-absensi'));
    }

    public function test_direktur_ikut_lolos(): void
    {
        $root = OrgUnit::factory()->create(['tipe' => OrgUnitTipe::Direktur]);
        $dir = Karyawan::factory()->pimpinanUnit($root)->create();

        $this->assertTrue(User::factory()->create(['karyawan_id' => $dir->id])->can('lihat-rekap-absensi'));
    }

    public function test_layar_laporan_hanya_menampilkan_subtree_koordinator(): void
    {
        [$userKoor, $milikSaya, $milikOrangLain] = $this->duaUnit();

        $this->actingAs($userKoor)->get('/absensi/laporan?dari=2026-08-01&sampai=2026-08-30')
            ->assertOk()
            ->assertSee($milikSaya->karyawan->nama_lengkap)
            ->assertDontSee($milikOrangLain->karyawan->nama_lengkap);
    }

    public function test_ekspor_juga_terbatas_subtree_koordinator(): void
    {
        [$userKoor, $milikSaya, $milikOrangLain] = $this->duaUnit();
        Excel::fake();
        Excel::matchByRegex();

        $this->actingAs($userKoor)
            ->get('/absensi/laporan/unduh?format=xlsx&dari=2026-08-01&sampai=2026-08-30')
            ->assertOk();

        Excel::assertDownloaded('/laporan-absensi.*\.xlsx/', function (AbsensiExport $e) use ($milikSaya, $milikOrangLain) {
            $ids = $e->collection()->pluck('karyawan_id')->all();

            return in_array($milikSaya->karyawan_id, $ids, true)
                && ! in_array($milikOrangLain->karyawan_id, $ids, true);
        });
    }

    public function test_ekspor_per_unit_tak_membocorkan_unit_luar(): void
    {
        [$userKoor, , $milikOrangLain] = $this->duaUnit();
        Excel::fake();
        Excel::matchByRegex();

        $this->actingAs($userKoor)
            ->get('/absensi/laporan/unduh?mode=per-unit&format=xlsx&dari=2026-08-01&sampai=2026-08-30')
            ->assertOk();

        Excel::assertDownloaded('/laporan-absensi.*\.xlsx/', function (AbsensiPerUnitExport $e) use ($milikOrangLain) {
            $semua = collect($e->sheets())->flatMap(fn ($s) => $s->collection());

            return ! $semua->pluck('karyawan_id')->contains($milikOrangLain->karyawan_id);
        });
    }

    /** @return array{0:User,1:Absensi,2:Absensi} */
    private function duaUnit(): array
    {
        $unitA = OrgUnit::factory()->create();
        $unitB = OrgUnit::factory()->create();

        $koor = Karyawan::factory()->pimpinanUnit($unitA)->create();
        $anggota = Karyawan::factory()->staffUnit($unitA)->create();
        $luar = Karyawan::factory()->staffUnit($unitB)->create();

        $a = Absensi::factory()->create(['karyawan_id' => $anggota->id, 'tanggal_kerja' => '2026-08-04']);
        $b = Absensi::factory()->create(['karyawan_id' => $luar->id, 'tanggal_kerja' => '2026-08-04']);

        return [User::factory()->create(['karyawan_id' => $koor->id]), $a, $b];
    }
}
