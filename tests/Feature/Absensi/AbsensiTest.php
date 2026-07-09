<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sesi_selesai_menghitung_total_menit(): void
    {
        $a = Absensi::factory()->create([
            'jam_masuk' => '2026-07-09 07:00:00',
            'jam_pulang' => '2026-07-09 14:30:00',
        ]);

        $this->assertFalse($a->sesiAktif());
        $this->assertSame(450, $a->totalMenit());   // 7j30m
        $this->assertInstanceOf(Karyawan::class, $a->karyawan);
    }

    public function test_sesi_aktif_saat_belum_pulang(): void
    {
        $a = Absensi::factory()->aktif()->create();
        $this->assertTrue($a->sesiAktif());
        $this->assertNull($a->totalMenit());
        $this->assertCount(1, Absensi::aktif()->get());
    }

    public function test_ada_shift_snapshot(): void
    {
        $tanpa = Absensi::factory()->create(['shift_mulai' => null, 'shift_selesai' => null]);
        $dengan = Absensi::factory()->create(['shift_mulai' => '07:00:00', 'shift_selesai' => '14:00:00']);
        $this->assertFalse($tanpa->adaShift());
        $this->assertTrue($dengan->adaShift());
    }

    public function test_anomali_saat_sesi_nyangkut_hari_lampau(): void
    {
        $nyangkut = Absensi::factory()->aktif()->create([
            'tanggal_kerja' => now()->subDays(2)->toDateString(),
            'jam_masuk' => now()->subDays(2),
        ]);
        $normal = Absensi::factory()->aktif()->create([
            'tanggal_kerja' => now()->toDateString(),
            'jam_masuk' => now(),
        ]);
        $this->assertTrue($nyangkut->anomali());
        $this->assertFalse($normal->anomali());
    }
}
