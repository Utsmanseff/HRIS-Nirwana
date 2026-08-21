<?php

namespace Tests\Feature\Absensi;

use App\Support\EvaluasiAbsensi;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EvaluasiAbsensiTest extends TestCase
{
    public function test_telat_menit_dihitung_dari_jam_mulai_saat_lewat_toleransi(): void
    {
        // shift mulai 14:00, toleransi 15m, masuk 14:18 → lewat toleransi → telat 18m (dari jam mulai)
        $telat = EvaluasiAbsensi::telatMenit(Carbon::parse('2026-07-09 14:18:00'), '14:00:00', 15);
        $this->assertSame(18, $telat);
    }

    public function test_tidak_telat_saat_masih_dalam_toleransi(): void
    {
        // masuk 14:10, toleransi 15m → 0
        $telat = EvaluasiAbsensi::telatMenit(Carbon::parse('2026-07-09 14:10:00'), '14:00:00', 15);
        $this->assertSame(0, $telat);
    }

    public function test_telat_shift_malam_saat_masuk_lewat_tengah_malam(): void
    {
        // Shift mulai 21:00, toleransi 15m, baru masuk 00:30 keesokan harinya → telat 210m.
        // Sebelum diperbaiki hasilnya 0 karena patokannya meleset ke 21:00 malam berikutnya.
        $telat = EvaluasiAbsensi::telatMenit(Carbon::parse('2026-07-10 00:30:00'), '21:00:00', 15);
        $this->assertSame(210, $telat);
    }

    public function test_datang_lebih_awal_pada_shift_malam_tidak_telat(): void
    {
        $telat = EvaluasiAbsensi::telatMenit(Carbon::parse('2026-07-09 20:30:00'), '21:00:00', 15);
        $this->assertSame(0, $telat);
    }

    public function test_pulang_cepat_menit(): void
    {
        // shift 07:00–14:00, pulang 13:40 → pulang cepat 20m
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 07:00:00'),
            Carbon::parse('2026-07-09 13:40:00'),
            '07:00:00', '14:00:00'
        );
        $this->assertSame(20, $pc);
    }

    public function test_tidak_pulang_cepat_saat_lewat_jam_selesai(): void
    {
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 07:00:00'),
            Carbon::parse('2026-07-09 14:12:00'),
            '07:00:00', '14:00:00'
        );
        $this->assertSame(0, $pc);
    }

    public function test_pulang_cepat_shift_malam_lintas_hari(): void
    {
        // shift 21:00–07:00 (lintas hari). Masuk 21:00 tgl 9, pulang 06:30 tgl 10 → pulang cepat 30m.
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 21:00:00'),
            Carbon::parse('2026-07-10 06:30:00'),
            '21:00:00', '07:00:00'
        );
        $this->assertSame(30, $pc);
    }

    public function test_masuk_lebih_awal_dan_pulang_lebih_awal_tidak_dihitung_pulang_cepat(): void
    {
        // Shift 09:00-16:00 (7 jam). Masuk 08:00, pulang 15:00 → kerja tetap 7 jam → normal.
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 08:00:00'),
            Carbon::parse('2026-07-09 15:00:00'),
            '09:00:00', '16:00:00'
        );
        $this->assertSame(0, $pc);
    }

    public function test_kekurangan_dihitung_dari_durasi_shift_bukan_jam_selesai(): void
    {
        // Masuk 08:58, pulang 15:56 → kerja 6j58m, kurang 2 menit dari 7 jam → 2m (bukan 4m).
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 08:58:00'),
            Carbon::parse('2026-07-09 15:56:00'),
            '09:00:00', '16:00:00'
        );
        $this->assertSame(2, $pc);
    }

    public function test_datang_telat_wajib_pulang_mundur_sepanjang_shift(): void
    {
        // Masuk 09:10, pulang 16:00 → kerja 6j50m dari 7 jam → kurang 10m.
        // Kewajibannya panjang shift, jadi telat menggeser jam pulang, bukan dimaafkan.
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 09:10:00'),
            Carbon::parse('2026-07-09 16:00:00'),
            '09:00:00', '16:00:00'
        );
        $this->assertSame(10, $pc);
    }

    public function test_telat_lalu_pulang_lebih_awal_menjumlah_dua_kekurangan(): void
    {
        // Masuk 09:10, pulang 15:50 → kerja 6j40m dari 7 jam → kurang 20m.
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 09:10:00'),
            Carbon::parse('2026-07-09 15:50:00'),
            '09:00:00', '16:00:00'
        );
        $this->assertSame(20, $pc);
    }

    public function test_shift_malam_masuk_lewat_tengah_malam(): void
    {
        // Shift 21:00-07:00 (10 jam), baru masuk 00:30 tgl 10, pulang 07:00 → kerja 6j30m
        // → kurang 3j30m. Rumus lama menempelkan jam shift ke tanggal jam_masuk sehingga
        // patokannya meleset sehari dan hasilnya kacau.
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-10 00:30:00'),
            Carbon::parse('2026-07-10 07:00:00'),
            '21:00:00', '07:00:00'
        );
        $this->assertSame(210, $pc);
    }

    public function test_durasi_shift_lintas_hari_dihitung_tanpa_tanggal(): void
    {
        $this->assertSame(420, EvaluasiAbsensi::durasiShiftMenit('09:00:00', '16:00:00'));
        $this->assertSame(600, EvaluasiAbsensi::durasiShiftMenit('21:00:00', '07:00:00'));
    }

    public function test_masuk_awal_tapi_kurang_jam_tetap_pulang_cepat(): void
    {
        // Masuk 08:00, pulang 14:00 → kerja 6 jam dari 7 jam → 60m.
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 08:00:00'),
            Carbon::parse('2026-07-09 14:00:00'),
            '09:00:00', '16:00:00'
        );
        $this->assertSame(60, $pc);
    }

    public function test_shift_malam_masuk_lebih_awal_ikut_terhitung(): void
    {
        // Shift 21:00-07:00 (10 jam). Masuk 20:30, pulang 06:30 → genap 10 jam → normal.
        $pc = EvaluasiAbsensi::pulangCepatMenit(
            Carbon::parse('2026-07-09 20:30:00'),
            Carbon::parse('2026-07-10 06:30:00'),
            '21:00:00', '07:00:00'
        );
        $this->assertSame(0, $pc);
    }
}
