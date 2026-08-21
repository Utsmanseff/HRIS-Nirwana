<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HitungUlangPulangCepatTest extends TestCase
{
    use RefreshDatabase;

    private function sesiLama(int $pulangCepatLama): Absensi
    {
        // Shift 09:00-16:00, masuk 08:00, pulang 15:00 → jam kerja genap 7 jam.
        // Aturan lama menulis 60m karena membandingkan ke jam selesai shift.
        return Absensi::factory()->create([
            'karyawan_id' => Karyawan::factory()->create()->id,
            'tanggal_kerja' => '2026-07-09',
            'jam_masuk' => '2026-07-09 08:00:00',
            'jam_pulang' => '2026-07-09 15:00:00',
            'shift_mulai' => '09:00:00',
            'shift_selesai' => '16:00:00',
            'pulang_cepat_menit' => $pulangCepatLama,
        ]);
    }

    public function test_sesi_lama_dihitung_ulang(): void
    {
        $a = $this->sesiLama(60);

        $this->artisan('absensi:hitung-ulang-pulang-cepat')->assertSuccessful();

        $this->assertSame(0, $a->refresh()->pulang_cepat_menit);
    }

    public function test_uji_coba_tidak_menyimpan(): void
    {
        $a = $this->sesiLama(60);

        $this->artisan('absensi:hitung-ulang-pulang-cepat', ['--uji-coba' => true])->assertSuccessful();

        $this->assertSame(60, $a->refresh()->pulang_cepat_menit);
    }

    public function test_sesi_tanpa_shift_dilewati(): void
    {
        $a = Absensi::factory()->create([
            'karyawan_id' => Karyawan::factory()->create()->id,
            'shift_mulai' => null,
            'shift_selesai' => null,
            'pulang_cepat_menit' => null,
        ]);

        $this->artisan('absensi:hitung-ulang-pulang-cepat')->assertSuccessful();

        $this->assertNull($a->refresh()->pulang_cepat_menit);
    }
}
