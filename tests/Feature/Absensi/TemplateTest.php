<?php

namespace Tests\Feature\Absensi;

use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use Illuminate\Database\QueryException;
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

    public function test_satu_unit_boleh_punya_banyak_pola(): void
    {
        $unit = OrgUnit::factory()->create();

        TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola CS IGD', 'tanggal_jangkar' => '2026-07-01']);
        TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola CS Poli', 'tanggal_jangkar' => '2026-07-01']);

        $this->assertSame(2, TemplateJadwal::where('org_unit_id', $unit->id)->count());
    }

    public function test_nama_pola_unik_per_unit(): void
    {
        $unit = OrgUnit::factory()->create();
        TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola CS IGD', 'tanggal_jangkar' => '2026-07-01']);

        $this->expectException(QueryException::class);
        TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola CS IGD', 'tanggal_jangkar' => '2026-07-01']);
    }

    public function test_nama_pola_sama_di_unit_berbeda_diterima(): void
    {
        $a = OrgUnit::factory()->create();
        $b = OrgUnit::factory()->create();

        TemplateJadwal::create(['org_unit_id' => $a->id, 'nama' => 'Pola Pagi', 'tanggal_jangkar' => '2026-07-01']);
        TemplateJadwal::create(['org_unit_id' => $b->id, 'nama' => 'Pola Pagi', 'tanggal_jangkar' => '2026-07-01']);

        $this->assertSame(2, TemplateJadwal::where('nama', 'Pola Pagi')->count());
    }
}
