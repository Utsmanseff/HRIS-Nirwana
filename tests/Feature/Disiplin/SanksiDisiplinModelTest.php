<?php

namespace Tests\Feature\Disiplin;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanksiDisiplinModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_buat_sanksi_cast_enum_dan_relasi(): void
    {
        $kena = Karyawan::factory()->create();
        $pengusul = Karyawan::factory()->create();

        $sanksi = SanksiDisiplin::create([
            'karyawan_id' => $kena->id,
            'pengusul_id' => $pengusul->id,
            'tingkat' => TingkatSanksi::Teguran1,
            'uraian' => 'Mangkir 3 hari.',
            'tanggal_kejadian' => '2026-07-01',
            'status' => StatusSanksi::Diajukan,
        ]);

        $sanksi->refresh();
        $this->assertInstanceOf(TingkatSanksi::class, $sanksi->tingkat);
        $this->assertInstanceOf(StatusSanksi::class, $sanksi->status);
        $this->assertSame($kena->id, $sanksi->karyawan->id);
        $this->assertSame($pengusul->id, $sanksi->pengusul->id);
    }

    public function test_relasi_karyawan_sanksi_dan_usulan(): void
    {
        $kena = Karyawan::factory()->create();
        $pengusul = Karyawan::factory()->create();
        SanksiDisiplin::factory()->create([
            'karyawan_id' => $kena->id,
            'pengusul_id' => $pengusul->id,
        ]);

        $this->assertCount(1, $kena->sanksiDisiplin);
        $this->assertCount(1, $pengusul->usulanSanksi);
    }
}
