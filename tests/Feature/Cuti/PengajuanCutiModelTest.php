<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Models\PengajuanCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanCutiModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_membuat_pengajuan_dengan_relasi(): void
    {
        $this->seed(JenisCutiSeeder::class);
        $p = PengajuanCuti::factory()->create();

        $this->assertNotNull($p->karyawan);
        $this->assertNotNull($p->jenisCuti);
        $this->assertInstanceOf(StatusPengajuanCuti::class, $p->status);
        $this->assertTrue($p->tanggal_mulai->lessThanOrEqualTo($p->tanggal_selesai));
    }

    public function test_status_default_diajukan(): void
    {
        $this->seed(JenisCutiSeeder::class);
        $p = PengajuanCuti::factory()->create();
        $this->assertSame(StatusPengajuanCuti::Diajukan, $p->status);
    }
}
