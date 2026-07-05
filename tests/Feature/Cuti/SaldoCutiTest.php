<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Enums\StatusPengajuanCuti;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\PengajuanCuti;
use App\Models\PenyesuaianSaldo;
use App\Models\User;
use App\Support\SaldoCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SaldoCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    private function karyawanPkwt(string $mulaiPkwt): Karyawan
    {
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => $mulaiPkwt,
            'tanggal_akhir' => Carbon::parse($mulaiPkwt)->addYears(2),
        ]);

        return $kar->refresh();
    }

    public function test_belum_eligible_sebelum_satu_tahun(): void
    {
        Carbon::setTestNow('2026-08-01');
        $kar = $this->karyawanPkwt('2026-03-01'); // baru 5 bulan
        $saldo = SaldoCuti::untuk($kar);

        $this->assertFalse($saldo->eligible());
        $this->assertNull($saldo->periodeMulai());
        $this->assertSame(0, $saldo->jatah());
        $this->assertSame(0, $saldo->efektif());
        Carbon::setTestNow();
    }

    public function test_eligible_jatah_dua_belas_penuh(): void
    {
        Carbon::setTestNow('2027-06-01'); // >1 tahun sejak 2026-03-01
        $kar = $this->karyawanPkwt('2026-03-01');
        $saldo = SaldoCuti::untuk($kar);

        $this->assertTrue($saldo->eligible());
        $this->assertSame('2027-03-01', $saldo->periodeMulai()->format('Y-m-d'));
        $this->assertSame('2028-03-01', $saldo->periodeSelesai()->format('Y-m-d'));
        $this->assertSame(12, $saldo->jatah());
        $this->assertSame(12, $saldo->efektif());
        Carbon::setTestNow();
    }

    public function test_terpakai_dan_pending_mengurangi_efektif_dalam_periode(): void
    {
        Carbon::setTestNow('2027-06-01');
        $kar = $this->karyawanPkwt('2026-03-01');

        // disetujui 4 hari dalam periode aktif → terpakai
        PengajuanCuti::factory()->for($kar)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(StatusPengajuanCuti::Disetujui)
            ->rentang('2027-05-04', '2027-05-07', 4)->create();
        // diajukan 2 hari dalam periode → pending
        PengajuanCuti::factory()->for($kar)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(StatusPengajuanCuti::Diajukan)
            ->rentang('2027-07-01', '2027-07-02', 2)->create();
        // disetujui tapi DI LUAR periode (periode lalu) → tak dihitung
        PengajuanCuti::factory()->for($kar)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(StatusPengajuanCuti::Disetujui)
            ->rentang('2026-12-01', '2026-12-03', 3)->create();

        $saldo = SaldoCuti::untuk($kar);
        $this->assertSame(4, $saldo->terpakai());
        $this->assertSame(2, $saldo->pending());
        $this->assertSame(6, $saldo->efektif()); // 12 - 4 - 2
        Carbon::setTestNow();
    }

    public function test_izin_biasa_tidak_mengurangi_saldo(): void
    {
        Carbon::setTestNow('2027-06-01');
        $kar = $this->karyawanPkwt('2026-03-01');
        PengajuanCuti::factory()->for($kar)->jenis(KodeJenisCuti::IzinBiasa)
            ->status(StatusPengajuanCuti::Disetujui)
            ->rentang('2027-05-04', '2027-05-08', 5)->create();

        $this->assertSame(12, SaldoCuti::untuk($kar)->efektif());
        Carbon::setTestNow();
    }

    public function test_penyesuaian_menambah_atau_mengurangi_jatah(): void
    {
        Carbon::setTestNow('2027-06-01');
        $kar = $this->karyawanPkwt('2026-03-01');
        $hrd = User::factory()->create();

        PenyesuaianSaldo::create([
            'karyawan_id' => $kar->id, 'periode_mulai' => '2027-03-01',
            'delta' => 3, 'alasan' => 'Bonus', 'dibuat_oleh' => $hrd->id,
        ]);
        PenyesuaianSaldo::create([
            'karyawan_id' => $kar->id, 'periode_mulai' => '2026-03-01', // periode lain → diabaikan
            'delta' => 5, 'alasan' => 'Lama', 'dibuat_oleh' => $hrd->id,
        ]);

        $this->assertSame(15, SaldoCuti::untuk($kar)->jatah()); // 12 + 3
        Carbon::setTestNow();
    }

    public function test_tanpa_pkwt_tidak_eligible(): void
    {
        $kar = Karyawan::factory()->create(); // tak ada kontrak PKWT
        $this->assertFalse(SaldoCuti::untuk($kar)->eligible());
        $this->assertSame(0, SaldoCuti::untuk($kar)->jatah());
    }

    public function test_penyesuaian_yatim_saat_anchor_bergeser_terdokumentasi(): void
    {
        // Mendokumentasikan bahaya: penyesuaian dikaitkan ke tanggal periode absolut.
        Carbon::setTestNow('2027-06-01');
        $kar = $this->karyawanPkwt('2026-03-01'); // periode aktif mulai 2027-03-01
        $hrd = User::factory()->create();

        // Penyesuaian benar (cocok periode aktif) → terhitung.
        PenyesuaianSaldo::create(['karyawan_id' => $kar->id, 'periode_mulai' => '2027-03-01', 'delta' => 3, 'alasan' => 'ok', 'dibuat_oleh' => $hrd->id]);
        $this->assertSame(15, SaldoCuti::untuk($kar)->jatah());

        // Anchor bergeser 1 bulan (mis. koreksi kontrak) → periode aktif jadi 2027-04-01.
        $kar->kontrak()->first()->update(['tanggal_mulai' => '2026-04-01']);
        $kar->refresh();

        // Penyesuaian 2027-03-01 kini YATIM (tak cocok periode) → jatah balik 12, delta hilang senyap.
        $this->assertSame('2027-04-01', SaldoCuti::untuk($kar)->periodeMulai()->format('Y-m-d'));
        $this->assertSame(12, SaldoCuti::untuk($kar)->jatah());
        Carbon::setTestNow();
    }
}
