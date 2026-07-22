<?php

namespace Tests\Feature\Cuti;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\Shift;
use App\Support\ProsesPengganti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProsesPenggantiBentrokTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $pemohon;

    protected Karyawan $pengganti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->pengganti = Karyawan::factory()->staffUnit($this->unit)->create();
    }

    private function shift(string $kode, string $mulai, string $selesai): Shift
    {
        return Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => $kode, 'nama' => $kode,
            'jam_mulai' => $mulai, 'jam_selesai' => $selesai,
        ]);
    }

    private function cuti(string $mulai, string $selesai): PengajuanCuti
    {
        return PengajuanCuti::factory()->rentang($mulai, $selesai, 2)->create([
            'karyawan_id' => $this->pemohon->id,
        ]);
    }

    public function test_tanpa_jadwal_pengganti_tidak_bentrok(): void
    {
        $pagi = $this->shift('M', '07:00:00', '14:00:00');
        Jadwal::factory()->create(['karyawan_id' => $this->pemohon->id, 'tanggal' => '2026-08-01', 'shift_id' => $pagi->id]);

        $hasil = ProsesPengganti::cekBentrok($this->pengganti, $this->cuti('2026-08-01', '2026-08-02'));

        $this->assertSame([], $hasil);
    }

    public function test_jam_beririsan_terdeteksi_dengan_detail(): void
    {
        $pagi = $this->shift('M', '07:00:00', '14:00:00');
        $siang = $this->shift('S', '13:00:00', '20:00:00');
        Jadwal::factory()->create(['karyawan_id' => $this->pemohon->id, 'tanggal' => '2026-08-01', 'shift_id' => $pagi->id]);
        Jadwal::factory()->create(['karyawan_id' => $this->pengganti->id, 'tanggal' => '2026-08-01', 'shift_id' => $siang->id]);

        $hasil = ProsesPengganti::cekBentrok($this->pengganti, $this->cuti('2026-08-01', '2026-08-02'));

        $this->assertCount(1, $hasil);
        $this->assertSame('2026-08-01', $hasil[0]['tanggal']);
        $this->assertSame('M', $hasil[0]['shift_pemohon']);
        $this->assertSame('S', $hasil[0]['shift_pengganti']);
    }

    public function test_shift_bersambung_ujung_bukan_bentrok(): void
    {
        $pagi = $this->shift('M', '07:00:00', '14:00:00');
        $sore = $this->shift('S', '14:00:00', '21:00:00');
        Jadwal::factory()->create(['karyawan_id' => $this->pemohon->id, 'tanggal' => '2026-08-01', 'shift_id' => $pagi->id]);
        Jadwal::factory()->create(['karyawan_id' => $this->pengganti->id, 'tanggal' => '2026-08-01', 'shift_id' => $sore->id]);

        $this->assertSame([], ProsesPengganti::cekBentrok($this->pengganti, $this->cuti('2026-08-01', '2026-08-02')));
    }

    public function test_hari_pemohon_libur_dilewati(): void
    {
        $malam = $this->shift('P', '22:00:00', '06:00:00');
        // Pemohon tak punya jadwal 2026-08-02; pengganti punya. Bukan bentrok.
        Jadwal::factory()->create(['karyawan_id' => $this->pengganti->id, 'tanggal' => '2026-08-02', 'shift_id' => $malam->id]);

        $this->assertSame([], ProsesPengganti::cekBentrok($this->pengganti, $this->cuti('2026-08-01', '2026-08-02')));
    }

    public function test_lintas_tengah_malam_beririsan(): void
    {
        $malam = $this->shift('P', '22:00:00', '06:00:00');
        $malam2 = $this->shift('D', '23:00:00', '04:00:00');
        Jadwal::factory()->create(['karyawan_id' => $this->pemohon->id, 'tanggal' => '2026-08-01', 'shift_id' => $malam->id]);
        Jadwal::factory()->create(['karyawan_id' => $this->pengganti->id, 'tanggal' => '2026-08-01', 'shift_id' => $malam2->id]);

        $hasil = ProsesPengganti::cekBentrok($this->pengganti, $this->cuti('2026-08-01', '2026-08-02'));

        $this->assertCount(1, $hasil);
    }

    public function test_rentang_sebagian_hanya_hari_dalam_rentang(): void
    {
        $pagi = $this->shift('M', '07:00:00', '14:00:00');
        $pagi2 = $this->shift('N', '08:00:00', '15:00:00');
        foreach (['2026-08-01', '2026-08-03'] as $tgl) {
            Jadwal::factory()->create(['karyawan_id' => $this->pemohon->id, 'tanggal' => $tgl, 'shift_id' => $pagi->id]);
            Jadwal::factory()->create(['karyawan_id' => $this->pengganti->id, 'tanggal' => $tgl, 'shift_id' => $pagi2->id]);
        }

        $hasil = ProsesPengganti::cekBentrok(
            $this->pengganti,
            $this->cuti('2026-08-01', '2026-08-03'),
            Carbon::parse('2026-08-02'),
            Carbon::parse('2026-08-03'),
        );

        $this->assertCount(1, $hasil);
        $this->assertSame('2026-08-03', $hasil[0]['tanggal']);
    }
}
