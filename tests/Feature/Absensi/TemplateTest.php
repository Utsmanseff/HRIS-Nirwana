<?php

namespace Tests\Feature\Absensi;

use App\Models\Karyawan;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_punya_baris_pola_terurut(): void
    {
        $tpl = TemplateJadwal::factory()->create(['tanggal_jangkar' => '2026-07-01']);
        $kar = Karyawan::factory()->create();
        $shift = Shift::factory()->create();

        PolaJadwal::factory()->create(['template_id' => $tpl->id, 'karyawan_id' => $kar->id, 'posisi' => 1, 'shift_id' => $shift->id]);
        PolaJadwal::factory()->create(['template_id' => $tpl->id, 'karyawan_id' => $kar->id, 'posisi' => 0, 'shift_id' => null]);

        $baris = $tpl->baris()->orderBy('posisi')->get();
        $this->assertCount(2, $baris);
        $this->assertNull($baris[0]->shift_id);          // posisi 0 = libur
        $this->assertSame($shift->id, $baris[1]->shift_id);
        $this->assertEquals('2026-07-01', $tpl->tanggal_jangkar->toDateString());
    }
}
