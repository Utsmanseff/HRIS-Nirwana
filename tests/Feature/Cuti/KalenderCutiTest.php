<?php

namespace Tests\Feature\Cuti;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Support\KalenderCuti;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KalenderCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
    }

    private function izin(): JenisCuti
    {
        return JenisCuti::where('kode', 'izin_biasa')->firstOrFail();
    }

    private function karyawanDiUnit(OrgUnit $unit, string $nama = 'Orang'): Karyawan
    {
        return Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => $nama]);
    }

    private function pengajuan(Karyawan $kar, string $mulai, string $selesai, string $status): PengajuanCuti
    {
        return PengajuanCuti::factory()->for($kar)->for($this->izin(), 'jenisCuti')->create([
            'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai,
            'jumlah_hari' => 1, 'status' => $status,
        ]);
    }

    public function test_expand_pengajuan_lintas_hari(): void
    {
        $unit = OrgUnit::factory()->create();
        $kar = $this->karyawanDiUnit($unit, 'Budi');
        $this->pengajuan($kar, '2026-06-10', '2026-06-12', 'disetujui');

        $data = KalenderCuti::bulan([$unit->id], Carbon::parse('2026-06-15'));

        $this->assertArrayHasKey('2026-06-10', $data['hari']);
        $this->assertArrayHasKey('2026-06-11', $data['hari']);
        $this->assertArrayHasKey('2026-06-12', $data['hari']);
        $this->assertArrayNotHasKey('2026-06-13', $data['hari']);
        $this->assertSame('Budi', $data['hari']['2026-06-10']->first()['nama']);
    }

    public function test_clamp_ke_batas_bulan(): void
    {
        $unit = OrgUnit::factory()->create();
        $kar = $this->karyawanDiUnit($unit);
        // Mulai bulan lalu, selesai di bulan target.
        $this->pengajuan($kar, '2026-05-28', '2026-06-02', 'disetujui');

        $data = KalenderCuti::bulan([$unit->id], Carbon::parse('2026-06-15'));

        $this->assertArrayNotHasKey('2026-05-28', $data['hari']);
        $this->assertArrayHasKey('2026-06-01', $data['hari']);
        $this->assertArrayHasKey('2026-06-02', $data['hari']);
    }

    public function test_status_filter_sembunyikan_ditolak_dibatalkan(): void
    {
        $unit = OrgUnit::factory()->create();
        $kar = $this->karyawanDiUnit($unit);
        $this->pengajuan($kar, '2026-06-05', '2026-06-05', 'disetujui');
        $this->pengajuan($kar, '2026-06-06', '2026-06-06', 'diajukan');
        $this->pengajuan($kar, '2026-06-07', '2026-06-07', 'ditolak');
        $this->pengajuan($kar, '2026-06-08', '2026-06-08', 'dibatalkan');

        $data = KalenderCuti::bulan([$unit->id], Carbon::parse('2026-06-15'));

        $this->assertArrayHasKey('2026-06-05', $data['hari']);
        $this->assertArrayHasKey('2026-06-06', $data['hari']);
        $this->assertArrayNotHasKey('2026-06-07', $data['hari']);
        $this->assertArrayNotHasKey('2026-06-08', $data['hari']);
    }

    public function test_scope_unit_termasuk_turunan(): void
    {
        $induk = OrgUnit::factory()->create();
        $anak = OrgUnit::factory()->create(['parent_id' => $induk->id]);
        $lain = OrgUnit::factory()->create();

        $karAnak = $this->karyawanDiUnit($anak, 'Anak');
        $karLain = $this->karyawanDiUnit($lain, 'Lain');
        $this->pengajuan($karAnak, '2026-06-10', '2026-06-10', 'disetujui');
        $this->pengajuan($karLain, '2026-06-10', '2026-06-10', 'disetujui');

        $data = KalenderCuti::bulan(OrgUnit::denganTurunan($induk->id), Carbon::parse('2026-06-15'));

        $this->assertCount(1, $data['hari']['2026-06-10']);
    }

    public function test_unit_ids_kosong_hasil_kosong(): void
    {
        $unit = OrgUnit::factory()->create();
        $kar = $this->karyawanDiUnit($unit);
        $this->pengajuan($kar, '2026-06-10', '2026-06-10', 'disetujui');

        $data = KalenderCuti::bulan([], Carbon::parse('2026-06-15'));

        $this->assertSame([], $data['hari']);
    }
}
