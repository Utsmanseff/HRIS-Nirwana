<?php

namespace Tests\Feature\Absensi;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BentukJadwalOtomatisTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_membentuk_bulan_berjalan_plus_2_non_destruktif(): void
    {
        Carbon::setTestNow('2026-07-15 09:00:00');

        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->for($unit, 'orgUnit')->create();
        $lain = Shift::factory()->for($unit, 'orgUnit')->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);

        // Siklus rotasi [P] panjang 1 → tiap hari Pagi, jangkar 1 Jul.
        $tpl = TemplateJadwal::create(['org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01']);
        PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $kar->id, 'posisi' => 0, 'shift_id' => $shift->id]);

        // Manual existing di 20 Jul pakai $lain (harus tetap).
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20', 'shift_id' => $lain->id]);

        $this->artisan('absensi:bentuk-jadwal')->assertSuccessful();

        // Bulan berjalan (Jul), +1 (Agu), +2 (Sep) terbentuk.
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-10 00:00:00', 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-08-10 00:00:00', 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-09-10 00:00:00', 'shift_id' => $shift->id]);
        // Di luar jendela (Okt) tak dibentuk.
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-10-10 00:00:00']);
        // Manual 20 Jul tak tertimpa.
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20 00:00:00', 'shift_id' => $lain->id]);
    }
}
