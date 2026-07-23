<?php

namespace Tests\Feature\Absensi;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Support\ProsesAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kunci-regresi timezone WITA.
 *
 * RSU Nirwana ada di Kalimantan Selatan (UTC+8). Selama `app.timezone` masih UTC,
 * `now()` untuk absen pagi (00:00–08:00 WITA) jatuh di tanggal kalender SEBELUMNYA:
 * anchor `tanggal_kerja` salah hari, lookup jadwal ambil shift hari kemarin, dan
 * `telat_menit` meleset 8 jam. Test ini mengunci perilaku yang benar.
 */
class TimezoneAbsensiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function dataAbsen(Carbon $jam): array
    {
        return [
            'jam' => $jam,
            'foto_path' => 'absensi/x.webp',
            'lat' => -3.3194370,
            'long' => 114.5908730,
            'akurasi' => 12.0,
            'wajah_verif' => true,
            'flag_lokasi' => [],
        ];
    }

    /** Instant UTC yang setara 07:00 WITA, 21 Juli 2026. */
    private function pagiWita(): Carbon
    {
        return Carbon::create(2026, 7, 20, 23, 0, 0, 'UTC');
    }

    public function test_timezone_aplikasi_wita(): void
    {
        $this->assertSame('Asia/Makassar', config('app.timezone'));
        $this->assertSame('Asia/Makassar', date_default_timezone_get());
    }

    public function test_absen_pagi_anchor_ke_tanggal_kalender_wita(): void
    {
        Carbon::setTestNow($this->pagiWita());
        $kar = Karyawan::factory()->create();

        $sesi = ProsesAbsen::masuk($kar, $this->dataAbsen(now()));

        $this->assertSame('2026-07-21', $sesi->tanggal_kerja->toDateString());
        $this->assertSame('07:00', $sesi->jam_masuk->format('H:i'));
    }

    public function test_absen_pagi_menemukan_jadwal_hari_itu_dan_tidak_dihitung_telat(): void
    {
        Carbon::setTestNow($this->pagiWita());
        $kar = Karyawan::factory()->create();
        $shift = Shift::factory()->create([
            'nama' => 'Pagi', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 10,
        ]);
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-21', 'shift_id' => $shift->id]);

        $sesi = ProsesAbsen::masuk($kar, $this->dataAbsen(now()));

        $this->assertSame($shift->id, $sesi->shift_id, 'jadwal hari itu harus ketemu (bukan jadwal hari kemarin)');
        $this->assertSame(0, $sesi->telat_menit, 'masuk tepat jam shift tidak boleh dihitung telat');
    }
}
