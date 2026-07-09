<?php

namespace Tests\Feature\Absensi;

use App\Models\PengaturanAbsensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanAbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambil_membuat_baris_default_sekali(): void
    {
        $a = PengaturanAbsensi::ambil();
        $b = PengaturanAbsensi::ambil();

        $this->assertSame(1, $a->id);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, PengaturanAbsensi::count());
        $this->assertSame(100, $a->radius_m);
        $this->assertSame(30, $a->max_akurasi_m);
    }
}
