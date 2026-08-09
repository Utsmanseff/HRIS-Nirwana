<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\PengingatAbsenMasuk;
use App\Notifications\PengingatAbsenPulang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotifikasiPengingatAbsenTest extends TestCase
{
    use RefreshDatabase;

    public function test_notif_masuk_menyimpan_jadwal_id_dan_url(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $shift = Shift::factory()->create(['nama' => 'Pagi', 'jam_mulai' => '07:00:00']);
        $jadwal = Jadwal::factory()->create([
            'karyawan_id' => $kar->id, 'shift_id' => $shift->id, 'tanggal' => '2026-08-10',
        ]);

        $user->notify(new PengingatAbsenMasuk($jadwal->load('shift')));

        $notif = $user->notifications()->first();
        $this->assertSame('absen_masuk', $notif->data['jenis']);
        $this->assertSame($jadwal->id, $notif->data['jadwal_id']);
        $this->assertSame('/absensi', $notif->data['url']);
        $this->assertStringContainsString('Pagi', $notif->data['pesan']);
    }

    public function test_notif_pulang_menyimpan_absensi_id(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $sesi = Absensi::factory()->aktif()->create([
            'karyawan_id' => $kar->id,
            'tanggal_kerja' => '2026-08-10',
            'jam_masuk' => Carbon::parse('2026-08-10 07:00:00'),
        ]);

        $user->notify(new PengingatAbsenPulang($sesi));

        $notif = $user->notifications()->first();
        $this->assertSame('absen_pulang', $notif->data['jenis']);
        $this->assertSame($sesi->id, $notif->data['absensi_id']);
        $this->assertSame('/absensi', $notif->data['url']);
    }
}
