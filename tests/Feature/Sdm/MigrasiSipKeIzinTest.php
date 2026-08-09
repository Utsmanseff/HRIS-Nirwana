<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrasiSipKeIzinTest extends TestCase
{
    use RefreshDatabase;

    public function test_kolom_sip_lama_sudah_hilang(): void
    {
        $this->assertFalse(Schema::hasColumn('karyawan', 'sip_nomor'));
        $this->assertFalse(Schema::hasColumn('karyawan', 'sip_berlaku_mulai'));
        $this->assertFalse(Schema::hasColumn('karyawan', 'sip_berlaku_akhir'));
    }

    public function test_jenis_sip_tersedia_setelah_migrasi(): void
    {
        // Migrasi menyeed master supaya penyalinan data punya jenis_izin_id yang sah,
        // tanpa bergantung pada seeder dijalankan lebih dulu.
        $this->assertNotNull(JenisIzin::where('kode', KodeJenisIzin::Sip->value)->first());
    }

    public function test_baris_izin_bisa_dibuat_untuk_jenis_sip(): void
    {
        $sip = JenisIzin::where('kode', KodeJenisIzin::Sip->value)->first();
        $izin = IzinKaryawan::factory()->create(['jenis_izin_id' => $sip->id]);

        $this->assertSame($sip->id, $izin->jenis->id);
    }
}
