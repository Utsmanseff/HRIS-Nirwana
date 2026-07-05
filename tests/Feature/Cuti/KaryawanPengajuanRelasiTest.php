<?php

namespace Tests\Feature\Cuti;

use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanPengajuanRelasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_karyawan_punya_banyak_pengajuan_terbaru_dulu(): void
    {
        $this->seed(JenisCutiSeeder::class);
        $kar = Karyawan::factory()->create();
        PengajuanCuti::factory()->for($kar)->create(['created_at' => now()->subDay()]);
        $baru = PengajuanCuti::factory()->for($kar)->create(['created_at' => now()]);

        // Pengajuan milik karyawan lain tak ikut.
        PengajuanCuti::factory()->create();

        $this->assertCount(2, $kar->pengajuanCuti);
        $this->assertSame($baru->id, $kar->pengajuanCuti->first()->id);
    }
}
