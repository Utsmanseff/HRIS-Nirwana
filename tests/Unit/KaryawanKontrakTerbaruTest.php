<?php

namespace Tests\Unit;

use App\Enums\JenisKontrak;
use App\Models\Karyawan;
use App\Models\Kontrak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanKontrakTerbaruTest extends TestCase
{
    use RefreshDatabase;

    public function test_kontrak_terbaru_ambil_tanggal_mulai_terbaru(): void
    {
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $kar->id, 'jenis' => 'percobaan', 'tanggal_mulai' => '2024-01-01', 'tanggal_akhir' => '2024-03-31']);
        $baru = Kontrak::factory()->create(['karyawan_id' => $kar->id, 'jenis' => 'pkwt', 'tanggal_mulai' => '2024-04-01', 'tanggal_akhir' => '2025-03-31']);

        $this->assertTrue($kar->kontrakTerbaru->is($baru));
    }

    public function test_kontrak_terbaru_null_bila_tanpa_kontrak(): void
    {
        $kar = Karyawan::factory()->create();

        $this->assertNull($kar->kontrakTerbaru);
    }

    public function test_label_jenis_kontrak(): void
    {
        $this->assertSame('PKWT', JenisKontrak::Pkwt->label());
        $this->assertSame('Tetap', JenisKontrak::Tetap->label());
        $this->assertSame('Percobaan unpaid', JenisKontrak::PercobaanUnpaid->label());
        $this->assertSame('Percobaan', JenisKontrak::Percobaan->label());
    }
}
