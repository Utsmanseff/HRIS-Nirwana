<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokumenTest extends TestCase
{
    use RefreshDatabase;

    public function test_dokumen_milik_karyawan(): void
    {
        $kar = Karyawan::factory()->create();
        $d = Dokumen::create(['karyawan_id' => $kar->id, 'tipe' => 'ktp', 'path' => 'dokumen/ktp.webp', 'mime' => 'image/webp', 'ukuran' => 1234]);
        $this->assertEquals($kar->id, $d->karyawan->id);
        $this->assertTrue($kar->dokumen->contains($d));
    }
}
