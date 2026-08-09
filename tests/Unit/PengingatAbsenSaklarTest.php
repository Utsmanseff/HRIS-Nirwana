<?php

namespace Tests\Unit;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\PengaturanAbsensi;
use App\Models\Shift;
use App\Models\User;
use App\Support\PengingatAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatAbsenSaklarTest extends TestCase
{
    use RefreshDatabase;

    public function test_saklar_mati_membungkam_keduanya(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        User::factory()->create(['karyawan_id' => $kar->id]);
        $shift = Shift::factory()->create([
            'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 10,
        ]);
        Jadwal::factory()->create([
            'karyawan_id' => $kar->id, 'shift_id' => $shift->id, 'tanggal' => '2026-08-10',
        ]);
        Absensi::factory()->aktif()->create([
            'karyawan_id' => $kar->id,
            'tanggal_kerja' => '2026-08-10',
            'shift_mulai' => '07:00:00', 'shift_selesai' => '14:00:00',
            'jam_masuk' => Carbon::parse('2026-08-10 07:00:00'),
        ]);

        PengaturanAbsensi::ambil()->update(['pengingat_aktif' => false]);

        $waktu = Carbon::parse('2026-08-10 15:00:00');
        $this->assertCount(0, PengingatAbsen::masukTerlewat($waktu));
        $this->assertCount(0, PengingatAbsen::pulangTerlewat($waktu));
    }
}
