<?php

namespace Tests\Feature\Cuti;

use App\Enums\TipePengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Support\KasusPengganti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasusPenggantiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasus_lowongan_dari_karyawan_nonaktif(): void
    {
        $kar = Karyawan::factory()->create([
            'status' => 'nonaktif',
            'tanggal_nonaktif' => '2026-08-01',
        ]);

        $kasus = KasusPengganti::dari($kar);

        $this->assertSame(TipePengganti::Lowongan, $kasus->tipe);
        $this->assertSame($kar->id, $kasus->digantikan->id);
        $this->assertNull($kasus->cuti);
        $this->assertSame('2026-08-01', $kasus->mulai->toDateString());
        $this->assertNull($kasus->akhir);
    }

    public function test_batas_akhir_lowongan_ikut_jadwal_terakhir(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'nonaktif', 'tanggal_nonaktif' => '2026-08-01']);
        $shift = Shift::factory()->create();
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-08-20', 'shift_id' => $shift->id]);

        $this->assertSame('2026-08-20', KasusPengganti::dari($kar)->batasAkhir()->toDateString());
    }

    public function test_batas_akhir_null_bila_tak_ada_jejak_jadwal(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'nonaktif', 'tanggal_nonaktif' => '2026-08-01']);

        $this->assertNull(KasusPengganti::dari($kar)->batasAkhir());
    }
}
