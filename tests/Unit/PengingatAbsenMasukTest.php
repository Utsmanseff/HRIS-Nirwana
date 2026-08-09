<?php

namespace Tests\Unit;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use App\Support\PengingatAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatAbsenMasukTest extends TestCase
{
    use RefreshDatabase;

    /** Karyawan aktif + akun user + jadwal shift 07:00-14:00 toleransi 10 menit hari ini. */
    private function jadwalPagi(): Jadwal
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        User::factory()->create(['karyawan_id' => $kar->id]);
        $shift = Shift::factory()->create([
            'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 10,
        ]);

        return Jadwal::factory()->create([
            'karyawan_id' => $kar->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-08-10',
        ]);
    }

    public function test_sebelum_ambang_belum_diingatkan(): void
    {
        $this->jadwalPagi();

        // 07:00 + toleransi 10 + jeda 15 = 07:25. Pukul 07:20 masih diam.
        $hasil = PengingatAbsen::masukTerlewat(Carbon::parse('2026-08-10 07:20:00'));

        $this->assertCount(0, $hasil);
    }

    public function test_setelah_ambang_diingatkan(): void
    {
        $jadwal = $this->jadwalPagi();

        $hasil = PengingatAbsen::masukTerlewat(Carbon::parse('2026-08-10 07:30:00'));

        $this->assertCount(1, $hasil);
        $this->assertSame($jadwal->id, $hasil->first()->id);
    }

    public function test_lewat_jam_pulang_tidak_diingatkan_lagi(): void
    {
        $this->jadwalPagi();

        // Batas atas = jam_selesai 14:00. Pukul 14:30 sudah percuma.
        $hasil = PengingatAbsen::masukTerlewat(Carbon::parse('2026-08-10 14:30:00'));

        $this->assertCount(0, $hasil);
    }

    public function test_sudah_absen_tidak_diingatkan(): void
    {
        $jadwal = $this->jadwalPagi();
        Absensi::factory()->create([
            'karyawan_id' => $jadwal->karyawan_id,
            'tanggal_kerja' => '2026-08-10',
            'shift_id' => $jadwal->shift_id,
        ]);

        $this->assertCount(0, PengingatAbsen::masukTerlewat(Carbon::parse('2026-08-10 07:30:00')));
    }

    public function test_dinas_ganda_shift_kedua_tetap_diingatkan(): void
    {
        $jadwal = $this->jadwalPagi();

        // Shift sore di hari yang sama untuk karyawan yang sama.
        $sore = Shift::factory()->create([
            'jam_mulai' => '14:00:00', 'jam_selesai' => '21:00:00', 'toleransi_telat' => 10,
        ]);
        $jadwalSore = Jadwal::factory()->create([
            'karyawan_id' => $jadwal->karyawan_id,
            'shift_id' => $sore->id,
            'tanggal' => '2026-08-10',
        ]);

        // Shift pagi sudah diabsen, shift sore belum.
        Absensi::factory()->create([
            'karyawan_id' => $jadwal->karyawan_id,
            'tanggal_kerja' => '2026-08-10',
            'shift_id' => $jadwal->shift_id,
        ]);

        $hasil = PengingatAbsen::masukTerlewat(Carbon::parse('2026-08-10 14:30:00'));

        $this->assertCount(1, $hasil);
        $this->assertSame($jadwalSore->id, $hasil->first()->id);
    }
}
