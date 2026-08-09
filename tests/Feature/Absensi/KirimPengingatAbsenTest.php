<?php

namespace Tests\Feature\Absensi;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KirimPengingatAbsenTest extends TestCase
{
    use RefreshDatabase;

    private function jadwalTerlewat(): User
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $shift = Shift::factory()->create([
            'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 10,
        ]);
        Jadwal::factory()->create([
            'karyawan_id' => $kar->id, 'shift_id' => $shift->id,
            'tanggal' => Carbon::now()->toDateString(),
        ]);

        return $user;
    }

    public function test_kirim_pengingat_masuk_ke_karyawan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:30:00'));
        $user = $this->jadwalTerlewat();

        $this->artisan('absensi:kirim-pengingat')->assertSuccessful();

        $this->assertSame(1, $user->notifications()->count());
        Carbon::setTestNow();
    }

    /**
     * SENGAJA TANPA Notification::fake(): fake mencegat pengiriman sehingga baris
     * `notifications` tak pernah lahir — padahal baris itulah sumber kebenaran dedup.
     * Dengan fake, test ini akan hijau sambil menyembunyikan pengiriman ganda di produksi.
     * Aman dijalankan asli: tanpa baris push_subscriptions, kanal webpush tak melakukan apa-apa.
     */
    public function test_run_kedua_tidak_kirim_ulang(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:30:00'));
        $user = $this->jadwalTerlewat();

        $this->artisan('absensi:kirim-pengingat')->assertSuccessful();
        $this->artisan('absensi:kirim-pengingat')->assertSuccessful();

        $this->assertSame(1, $user->notifications()->count());
        Carbon::setTestNow();
    }

    public function test_karyawan_tanpa_akun_tidak_bikin_error(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:30:00'));
        $kar = Karyawan::factory()->create(['status' => 'aktif']); // tanpa User
        $shift = Shift::factory()->create([
            'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 10,
        ]);
        Jadwal::factory()->create([
            'karyawan_id' => $kar->id, 'shift_id' => $shift->id, 'tanggal' => '2026-08-10',
        ]);

        $this->artisan('absensi:kirim-pengingat')->assertSuccessful();

        $this->assertSame(0, DatabaseNotification::count());
        Carbon::setTestNow();
    }
}
