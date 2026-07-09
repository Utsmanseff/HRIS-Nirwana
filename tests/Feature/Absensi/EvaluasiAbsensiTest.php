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
}
