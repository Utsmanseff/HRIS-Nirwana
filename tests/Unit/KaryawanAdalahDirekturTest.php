<?php

namespace Tests\Unit;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanAdalahDirekturTest extends TestCase
{
    use RefreshDatabase;

    private function karyawanLevel(int $level): Karyawan
    {
        $unit = OrgUnit::factory()->create();
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => $level]);

        return Karyawan::factory()->create(['jabatan_id' => $jab->id, 'org_unit_id' => $unit->id]);
    }

    public function test_level_direktur_true(): void
    {
        $this->assertTrue($this->karyawanLevel(4)->adalahDirektur());
    }

    public function test_level_bawah_direktur_false(): void
    {
        foreach ([1, 2, 3] as $level) {
            $this->assertFalse($this->karyawanLevel($level)->adalahDirektur(), "level $level seharusnya bukan direktur");
        }
    }

    /**
     * karyawan.jabatan_id NOT NULL di skema, jadi keadaan ini mustahil tersimpan di DB.
     * Yang diuji di sini cabang null-safe pada model yang belum tersimpan — jangan
     * diubah jadi create(), skema akan menolaknya.
     */
    public function test_tanpa_jabatan_false(): void
    {
        $this->assertFalse((new Karyawan)->adalahDirektur());
    }
}
