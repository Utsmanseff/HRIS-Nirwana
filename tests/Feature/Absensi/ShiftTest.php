<?php

namespace Tests\Feature\Absensi;

use App\Models\OrgUnit;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_dibuat_dengan_cast_dan_relasi_unit(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->for($unit, 'orgUnit')->create([
            'nama' => 'Pagi', 'kode' => 'P', 'warna' => '#16A34A',
            'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 15,
        ]);

        $this->assertSame($unit->id, $shift->orgUnit->id);
        $this->assertSame(15, $shift->toleransi_telat);
        $this->assertTrue($shift->aktif);
        $this->assertFalse($shift->lintasHari());
    }

    public function test_shift_lintas_hari_saat_selesai_sebelum_mulai(): void
    {
        $shift = Shift::factory()->create(['jam_mulai' => '21:00:00', 'jam_selesai' => '07:00:00']);
        $this->assertTrue($shift->lintasHari());
    }

    public function test_scope_aktif_hanya_shift_aktif(): void
    {
        Shift::factory()->create(['aktif' => true]);
        Shift::factory()->create(['aktif' => false]);
        $this->assertCount(1, Shift::aktif()->get());
    }
}
