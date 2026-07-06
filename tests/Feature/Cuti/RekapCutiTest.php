<?php

namespace Tests\Feature\Cuti;

use App\Enums\JenisKontrak;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Support\RekapCuti;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RekapCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
    }

    private function jenis(string $kode): JenisCuti
    {
        return JenisCuti::where('kode', $kode)->firstOrFail();
    }

    public function test_hitung_status_dan_filter_periode(): void
    {
        $kar = Karyawan::factory()->create();
        $izin = $this->jenis('izin_biasa');

        // 2 dalam periode, beda status
        PengajuanCuti::factory()->for($kar)->for($izin, 'jenisCuti')->create([
            'tanggal_mulai' => '2026-06-10', 'tanggal_selesai' => '2026-06-10', 'jumlah_hari' => 1, 'status' => 'disetujui',
        ]);
        PengajuanCuti::factory()->for($kar)->for($izin, 'jenisCuti')->create([
            'tanggal_mulai' => '2026-06-20', 'tanggal_selesai' => '2026-06-20', 'jumlah_hari' => 1, 'status' => 'diajukan',
        ]);
        // 1 di luar periode
        PengajuanCuti::factory()->for($kar)->for($izin, 'jenisCuti')->create([
            'tanggal_mulai' => '2026-09-01', 'tanggal_selesai' => '2026-09-01', 'jumlah_hari' => 1, 'status' => 'disetujui',
        ]);

        $s = RekapCuti::hitungStatus(['dari' => '2026-06-01', 'sampai' => '2026-06-30']);

        $this->assertSame(1, $s['disetujui']);
        $this->assertSame(1, $s['diajukan']);
        $this->assertSame(0, $s['ditolak']);
    }

    public function test_filter_unit_termasuk_turunan(): void
    {
        $induk = OrgUnit::factory()->create();
        $anak = OrgUnit::factory()->create(['parent_id' => $induk->id]);
        $karAnak = Karyawan::factory()->create(['org_unit_id' => $anak->id]);
        $izin = $this->jenis('izin_biasa');

        PengajuanCuti::factory()->for($karAnak)->for($izin, 'jenisCuti')->create([
            'tanggal_mulai' => '2026-06-10', 'tanggal_selesai' => '2026-06-10', 'jumlah_hari' => 1, 'status' => 'disetujui',
        ]);

        $daftar = RekapCuti::daftarPengajuan(['unit_id' => $induk->id]);
        $this->assertCount(1, $daftar);
    }

    public function test_saldo_karyawan_hanya_eligible(): void
    {
        Carbon::setTestNow('2027-06-01');
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Wati']);
        Kontrak::factory()->for($kar)->create([
            'jenis' => JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        $saldo = RekapCuti::saldoKaryawan(null);
        $baris = $saldo->firstWhere('nip', $kar->nip);

        $this->assertNotNull($baris);
        $this->assertArrayHasKey('jatah', $baris);
        $this->assertArrayHasKey('sisa', $baris);
        Carbon::setTestNow();
    }

    public function test_jumlah_pending_org_wide(): void
    {
        $kar = Karyawan::factory()->create();
        $izin = $this->jenis('izin_biasa');
        foreach (['diajukan', 'diproses', 'disetujui'] as $st) {
            PengajuanCuti::factory()->for($kar)->for($izin, 'jenisCuti')->create([
                'tanggal_mulai' => '2026-06-10', 'tanggal_selesai' => '2026-06-10', 'jumlah_hari' => 1, 'status' => $st,
            ]);
        }

        $this->assertSame(2, RekapCuti::jumlahPendingOrgWide());
    }
}
