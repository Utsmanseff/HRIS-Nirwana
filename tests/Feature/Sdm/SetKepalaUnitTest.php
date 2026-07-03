<?php

namespace Tests\Feature\Sdm;

use App\Enums\OrgUnitTipe;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetKepalaUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_jabatan_pimpinan_dibuat_lazy_dengan_level_tipe(): void
    {
        $unit = OrgUnit::factory()->create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit]);
        $jab = $unit->jabatanPimpinan();
        $this->assertSame(2, $jab->level->value);            // unit → koordinator
        $this->assertSame($unit->id, $jab->org_unit_id);
        $this->assertSame('Koordinator Farmasi', $jab->nama);
        // Idempoten: panggil lagi → row sama.
        $this->assertSame($jab->id, $unit->jabatanPimpinan()->id);
    }

    public function test_nama_pimpinan_per_tipe(): void
    {
        $bidang = OrgUnit::factory()->create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang]);
        $bagian = OrgUnit::factory()->create(['nama' => 'Umum', 'tipe' => OrgUnitTipe::Bagian]);
        $dir = OrgUnit::factory()->create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur]);
        $this->assertSame('Kabid Penunjang', $bidang->jabatanPimpinan()->nama);
        $this->assertSame('Kabag Umum', $bagian->jabatanPimpinan()->nama);
        $this->assertSame('Direktur', $dir->jabatanPimpinan()->nama);
        $this->assertSame(3, $bidang->jabatanPimpinan()->level->value);
        $this->assertSame(4, $dir->jabatanPimpinan()->level->value);
    }

    public function test_set_kepala_unit_kosong(): void
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        $kar = \App\Models\Karyawan::factory()->staffUnit($unit)->create();
        $unit->setKepala($kar);
        $this->assertEquals($kar->id, $unit->kepala()->id);
        $this->assertGreaterThanOrEqual(2, $kar->fresh()->jabatan->level->value);
    }

    public function test_set_kepala_baru_demote_kepala_lama_jadi_staff(): void
    {
        $unit = \App\Models\OrgUnit::factory()->create(['nama' => 'IGD']);
        $lama = \App\Models\Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $baru = \App\Models\Karyawan::factory()->staffUnit($unit)->create();

        $unit->setKepala($baru);

        $this->assertEquals($baru->id, $unit->kepala()->id);
        // Kepala lama tetap di unit, tapi turun jadi staff (level 1).
        $this->assertSame(1, $lama->fresh()->jabatan->level->value);
        $this->assertSame($unit->id, $lama->fresh()->org_unit_id);
    }

    public function test_set_kepala_karyawan_dari_unit_lain_pindah_masuk(): void
    {
        $unitA = \App\Models\OrgUnit::factory()->create();
        $unitB = \App\Models\OrgUnit::factory()->create();
        $kar = \App\Models\Karyawan::factory()->staffUnit($unitA)->create();

        $unitB->setKepala($kar);

        $this->assertEquals($kar->id, $unitB->kepala()->id);
        $this->assertSame($unitB->id, $kar->fresh()->org_unit_id);
    }
}
