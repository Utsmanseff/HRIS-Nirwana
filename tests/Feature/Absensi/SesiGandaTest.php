<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Support\ProsesAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kunci-spec: model absensi adalah SESI, bukan per-tanggal.
 *
 * Satu karyawan boleh membuka lebih dari satu sesi dalam satu tanggal kalender.
 * Di RSU Nirwana dinas ganda itu nyata — misal shift 00:00-08:00 lalu masuk lagi
 * 16:00-00:00 di hari yang sama. Satu-satunya penghalang absen masuk adalah
 * SESI YANG MASIH TERBUKA, bukan riwayat harian.
 *
 * Jangan tambahkan guard "sudah absen hari ini" di ProsesAbsen::masuk() —
 * itu memutus dinas ganda. Kalau aturannya berubah, ubah spec dulu, lalu test ini.
 *
 * @see docs/superpowers/specs/2026-07-09-fase5-absensi-design.md §1 "Model SESI (state machine), bukan per-tanggal"
 */
class SesiGandaTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_dinas_ganda_dalam_satu_hari_diizinkan(): void
    {
        $kar = Karyawan::factory()->create();

        // Shift pertama 00:00-08:00.
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 00:00:00')));
        ProsesAbsen::pulang($kar, $this->dataAbsen(Carbon::parse('2026-07-20 08:00:00')));
        // Shift kedua hari yang sama 16:00-00:00.
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 16:00:00')));

        $this->assertSame(2, Absensi::where('karyawan_id', $kar->id)
            ->whereDate('tanggal_kerja', '2026-07-20')->count());
    }

    public function test_tidak_bisa_masuk_saat_sesi_masih_terbuka(): void
    {
        $kar = Karyawan::factory()->create();
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 00:00:00')));

        $this->expectExceptionMessage('Masih ada sesi aktif');
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 16:00:00')));
    }

    public function test_shift_malam_beruntun_dianchor_ke_tanggal_masuk(): void
    {
        $kar = Karyawan::factory()->create();

        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 22:00:00')));
        ProsesAbsen::pulang($kar, $this->dataAbsen(Carbon::parse('2026-07-21 06:00:00')));
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-21 22:00:00')));

        $this->assertSame(1, Absensi::where('karyawan_id', $kar->id)
            ->whereDate('tanggal_kerja', '2026-07-20')->count());
        $this->assertSame(1, Absensi::where('karyawan_id', $kar->id)
            ->whereDate('tanggal_kerja', '2026-07-21')->count());
    }

    public function test_sesi_nyangkut_ditutup_lalu_boleh_masuk_hari_itu(): void
    {
        $kar = Karyawan::factory()->create();

        // Lupa absen pulang kemarin.
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 07:00:00')));
        // Pagi ini tombol masih "Absen Pulang" — paksa tutup sesi lama.
        ProsesAbsen::pulang($kar, $this->dataAbsen(Carbon::parse('2026-07-21 07:00:00')));
        // Lalu absen masuk untuk hari ini.
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-21 07:01:00')));

        $this->assertSame(1, Absensi::where('karyawan_id', $kar->id)
            ->whereDate('tanggal_kerja', '2026-07-21')->count());
    }
}
