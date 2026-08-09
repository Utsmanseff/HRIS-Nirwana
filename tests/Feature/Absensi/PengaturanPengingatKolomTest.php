<?php

namespace Tests\Feature\Absensi;

use App\Models\PengaturanAbsensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanPengingatKolomTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_pengingat_terisi(): void
    {
        $p = PengaturanAbsensi::ambil();

        $this->assertTrue($p->pengingat_aktif);
        $this->assertSame(15, $p->jeda_masuk_menit);
        $this->assertSame(30, $p->jeda_pulang_menit);
        $this->assertSame(12, $p->ambang_nyangkut_jam);
    }

    public function test_pengingat_aktif_di_cast_boolean(): void
    {
        $p = PengaturanAbsensi::ambil();
        $p->update(['pengingat_aktif' => 0]);

        $this->assertFalse($p->fresh()->pengingat_aktif);
    }
}
