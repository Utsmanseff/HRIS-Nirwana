<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Support\AturanCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AturanCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    private function karyawanEligible(): Karyawan
    {
        Carbon::setTestNow('2027-06-01');
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        return $kar->refresh();
    }

    private function jenis(KodeJenisCuti $k): JenisCuti
    {
        return JenisCuti::where('kode', $k->value)->firstOrFail();
    }

    public function test_jenis_tersedia_saat_eligible_semua_aktif(): void
    {
        $kar = $this->karyawanEligible();
        $kode = AturanCuti::jenisTersedia($kar)->pluck('kode')->map(fn ($k) => $k->value)->all();
        $this->assertContains('cuti_tahunan', $kode);
        $this->assertContains('izin_biasa', $kode);
        Carbon::setTestNow();
    }

    public function test_jenis_tersedia_saat_belum_eligible_blokir_tahunan_saja(): void
    {
        Carbon::setTestNow('2026-08-01');
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);
        $kar->refresh();

        $kode = AturanCuti::jenisTersedia($kar)->pluck('kode')->map(fn ($k) => $k->value)->all();
        $this->assertNotContains('cuti_tahunan', $kode);
        $this->assertContains('izin_biasa', $kode);
        $this->assertContains('cuti_sakit', $kode);
        $this->assertContains('cuti_melahirkan', $kode);
        Carbon::setTestNow();
    }

    public function test_validasi_tanggal_selesai_sebelum_mulai_error(): void
    {
        $kar = $this->karyawanEligible();
        $err = AturanCuti::periksa($kar, $this->jenis(KodeJenisCuti::CutiTahunan), '2027-07-10', '2027-07-08', 1, false);
        $this->assertArrayHasKey('tanggalSelesai', $err);
        Carbon::setTestNow();
    }

    public function test_jumlah_hari_melebihi_rentang_error(): void
    {
        $kar = $this->karyawanEligible();
        // rentang 3 hari kalender (8,9,10) → jumlah_hari 4 tak valid
        $err = AturanCuti::periksa($kar, $this->jenis(KodeJenisCuti::CutiTahunan), '2027-07-08', '2027-07-10', 4, false);
        $this->assertArrayHasKey('jumlahHari', $err);
        Carbon::setTestNow();
    }

    public function test_cuti_tahunan_maks_enam(): void
    {
        $kar = $this->karyawanEligible();
        $err = AturanCuti::periksa($kar, $this->jenis(KodeJenisCuti::CutiTahunan), '2027-07-01', '2027-07-10', 7, false);
        $this->assertArrayHasKey('jumlahHari', $err);
        Carbon::setTestNow();
    }

    public function test_cuti_tahunan_melebihi_saldo_error(): void
    {
        $kar = $this->karyawanEligible(); // saldo efektif 12
        // 6 valid saldo, tapi buat saldo terpakai dulu agar efektif < 6
        \App\Models\PengajuanCuti::factory()->for($kar)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(\App\Enums\StatusPengajuanCuti::Disetujui)
            ->rentang('2027-06-02', '2027-06-09', 8)->create();
        // efektif = 12 - 8 = 4; minta 6 → error
        $err = AturanCuti::periksa($kar, $this->jenis(KodeJenisCuti::CutiTahunan), '2027-07-01', '2027-07-06', 6, false);
        $this->assertArrayHasKey('jumlahHari', $err);
        Carbon::setTestNow();
    }

    public function test_backdate_ditolak_untuk_cuti_tahunan(): void
    {
        Carbon::setTestNow('2027-06-15');
        $kar = $this->karyawanEligible();
        Carbon::setTestNow('2027-06-15');
        $err = AturanCuti::periksa($kar, $this->jenis(KodeJenisCuti::CutiTahunan), '2027-06-10', '2027-06-11', 2, false);
        $this->assertArrayHasKey('tanggalMulai', $err);
        Carbon::setTestNow();
    }

    public function test_backdate_boleh_untuk_cuti_sakit(): void
    {
        Carbon::setTestNow('2027-06-15');
        $kar = $this->karyawanEligible();
        Carbon::setTestNow('2027-06-15');
        // sakit backdate + lampiran ADA → tak ada error tanggal/lampiran
        $err = AturanCuti::periksa($kar, $this->jenis(KodeJenisCuti::CutiSakit), '2027-06-10', '2027-06-11', 2, true);
        $this->assertArrayNotHasKey('tanggalMulai', $err);
        $this->assertArrayNotHasKey('lampiran', $err);
        Carbon::setTestNow();
    }

    public function test_lampiran_wajib_untuk_izin_biasa(): void
    {
        $kar = $this->karyawanEligible();
        $err = AturanCuti::periksa($kar, $this->jenis(KodeJenisCuti::IzinBiasa), '2027-07-01', '2027-07-02', 2, false);
        $this->assertArrayHasKey('lampiran', $err);
        Carbon::setTestNow();
    }
}
