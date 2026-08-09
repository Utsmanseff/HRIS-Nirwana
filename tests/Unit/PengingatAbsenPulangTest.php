<?php

namespace Tests\Unit;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\User;
use App\Support\PengingatAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatAbsenPulangTest extends TestCase
{
    use RefreshDatabase;

    /** Sesi terbuka dengan snapshot shift. */
    private function sesiTerbuka(array $override = []): Absensi
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        User::factory()->create(['karyawan_id' => $kar->id]);

        return Absensi::factory()->aktif()->create(array_merge([
            'karyawan_id' => $kar->id,
            'tanggal_kerja' => '2026-08-10',
            'shift_nama' => 'Pagi',
            'shift_mulai' => '07:00:00',
            'shift_selesai' => '14:00:00',
            'shift_toleransi' => 10,
            'jam_masuk' => Carbon::parse('2026-08-10 07:00:00'),
        ], $override));
    }

    public function test_sebelum_ambang_belum_diingatkan(): void
    {
        $this->sesiTerbuka();

        // 14:00 + jeda 30 = 14:30. Pukul 14:20 masih diam.
        $this->assertCount(0, PengingatAbsen::pulangTerlewat(Carbon::parse('2026-08-10 14:20:00')));
    }

    public function test_setelah_ambang_diingatkan(): void
    {
        $sesi = $this->sesiTerbuka();

        $hasil = PengingatAbsen::pulangTerlewat(Carbon::parse('2026-08-10 14:40:00'));

        $this->assertCount(1, $hasil);
        $this->assertSame($sesi->id, $hasil->first()->id);
    }

    public function test_sesi_sudah_ditutup_tidak_diingatkan(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        User::factory()->create(['karyawan_id' => $kar->id]);
        Absensi::factory()->create([   // default factory = sudah pulang
            'karyawan_id' => $kar->id,
            'tanggal_kerja' => '2026-08-10',
            'shift_mulai' => '07:00:00',
            'shift_selesai' => '14:00:00',
            'jam_masuk' => Carbon::parse('2026-08-10 07:00:00'),
            'jam_pulang' => Carbon::parse('2026-08-10 14:05:00'),
        ]);

        $this->assertCount(0, PengingatAbsen::pulangTerlewat(Carbon::parse('2026-08-10 16:00:00')));
    }

    /** Shift malam 21:00-05:00: akhir shift jatuh di hari berikutnya, bukan hari yang sama. */
    public function test_shift_lintas_tengah_malam_dihitung_hari_berikutnya(): void
    {
        $this->sesiTerbuka([
            'shift_mulai' => '21:00:00',
            'shift_selesai' => '05:00:00',
            'jam_masuk' => Carbon::parse('2026-08-10 21:00:00'),
        ]);

        // Pukul 23:00 hari yang sama: belum waktunya (kalau salah hitung, ini akan bocor).
        $this->assertCount(0, PengingatAbsen::pulangTerlewat(Carbon::parse('2026-08-10 23:00:00')));

        // Pukul 05:40 hari berikutnya: 05:00 + jeda 30 = 05:30, sudah lewat.
        $this->assertCount(1, PengingatAbsen::pulangTerlewat(Carbon::parse('2026-08-11 05:40:00')));
    }
}
