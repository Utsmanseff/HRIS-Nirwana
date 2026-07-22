<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengganti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PenggantiCutiModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    public function test_kolom_baru_ada(): void
    {
        $this->assertTrue(Schema::hasColumn('org_units', 'pakai_pengganti'));
        $this->assertTrue(Schema::hasColumn('jadwal', 'pengganti_cuti_id'));
        $this->assertTrue(Schema::hasTable('pengganti_cuti'));
    }

    public function test_relasi_dan_cast(): void
    {
        $cuti = PengajuanCuti::factory()->create();
        $pengganti = Karyawan::factory()->create();

        $baris = PenggantiCuti::create([
            'pengajuan_cuti_id' => $cuti->id,
            'karyawan_id' => $pengganti->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-03',
            'status' => StatusPengganti::Aktif,
        ]);

        $this->assertInstanceOf(StatusPengganti::class, $baris->status);
        $this->assertSame('2026-08-01', $baris->tanggal_mulai->toDateString());
        $this->assertSame($cuti->id, $baris->pengajuan->id);
        $this->assertSame($pengganti->id, $baris->karyawan->id);
    }

    public function test_scope_aktif_dan_usulan(): void
    {
        $cuti = PengajuanCuti::factory()->create();
        PenggantiCuti::factory()->create(['pengajuan_cuti_id' => $cuti->id]);
        PenggantiCuti::factory()->usulan()->create(['pengajuan_cuti_id' => $cuti->id]);

        $this->assertCount(1, PenggantiCuti::aktif()->get());
        $this->assertCount(1, PenggantiCuti::usulan()->get());
    }

    public function test_hapus_pengajuan_menghapus_baris_pengganti(): void
    {
        $cuti = PengajuanCuti::factory()->create();
        PenggantiCuti::factory()->create(['pengajuan_cuti_id' => $cuti->id]);

        $cuti->delete();

        $this->assertSame(0, PenggantiCuti::count());
    }
}
