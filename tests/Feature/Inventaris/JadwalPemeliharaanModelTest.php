<?php

namespace Tests\Feature\Inventaris;

use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JadwalPemeliharaanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_berikutnya_dari_terakhir_plus_interval(): void
    {
        $aset = Aset::factory()->create();
        $j = JadwalPemeliharaan::factory()->for($aset)->create([
            'interval_bulan' => 6,
            'terakhir_dilakukan' => Carbon::parse('2026-01-10'),
        ]);
        $this->assertEquals('2026-07-10', $j->berikutnya()->format('Y-m-d'));
    }

    public function test_berikutnya_null_bila_belum_pernah(): void
    {
        $aset = Aset::factory()->create();
        $j = JadwalPemeliharaan::factory()->for($aset)->create([
            'interval_bulan' => 3,
            'terakhir_dilakukan' => null,
        ]);
        $this->assertNull($j->berikutnya());
    }
}
