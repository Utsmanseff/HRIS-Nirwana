<?php

namespace Tests\Feature\Absensi;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalTest extends TestCase
{
    use RefreshDatabase;

    public function test_jadwal_punya_relasi_karyawan_dan_shift(): void
    {
        $jadwal = Jadwal::factory()->create();
        $this->assertInstanceOf(Karyawan::class, $jadwal->karyawan);
        $this->assertInstanceOf(Shift::class, $jadwal->shift);
    }

    public function test_unique_karyawan_tanggal(): void
    {
        $kar = Karyawan::factory()->create();
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-09']);

        $this->expectException(QueryException::class);
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-09']);
    }
}
