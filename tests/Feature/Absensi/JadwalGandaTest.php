<?php

namespace Tests\Feature\Absensi;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\Shift;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kunci-spec: satu karyawan boleh punya BANYAK jadwal pada satu tanggal (dinas ganda),
 * tapi tidak boleh dua baris dengan shift yang sama.
 *
 * @see docs/superpowers/specs/2026-07-21-dinas-ganda-jadwal-design.md §3
 */
class JadwalGandaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dua_shift_berbeda_di_tanggal_sama_diizinkan(): void
    {
        $unit = OrgUnit::factory()->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $malam = Shift::factory()->for($unit, 'orgUnit')->create(['kode' => 'M', 'jam_mulai' => '00:00:00', 'jam_selesai' => '08:00:00']);
        $sore = Shift::factory()->for($unit, 'orgUnit')->create(['kode' => 'S', 'jam_mulai' => '16:00:00', 'jam_selesai' => '00:00:00']);

        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20', 'shift_id' => $malam->id]);
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20', 'shift_id' => $sore->id]);

        $this->assertSame(2, Jadwal::where('karyawan_id', $kar->id)->whereDate('tanggal', '2026-07-20')->count());
    }

    public function test_shift_kembar_di_tanggal_sama_ditolak_db(): void
    {
        $unit = OrgUnit::factory()->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $shift = Shift::factory()->for($unit, 'orgUnit')->create(['kode' => 'P']);

        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20', 'shift_id' => $shift->id]);

        $this->expectException(QueryException::class);
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20', 'shift_id' => $shift->id]);
    }
}
