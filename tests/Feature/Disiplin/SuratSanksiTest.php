<?php

namespace Tests\Feature\Disiplin;

use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Support\SuratSanksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratSanksiTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_menyimpan_pdf_di_disk_local(): void
    {
        Storage::fake('local');
        $kena = Karyawan::factory()->create(['nama_lengkap' => 'Dewi Kartika']);
        $sanksi = SanksiDisiplin::factory()
            ->diterbitkan(TingkatSanksi::Sp1)
            ->create(['karyawan_id' => $kena->id, 'nomor_surat' => '01.123/HRD/RSUN/VII/2026']);

        $path = SuratSanksi::generate($sanksi);

        $this->assertStringStartsWith("sanksi/{$sanksi->id}/", $path);
        Storage::disk('local')->assertExists($path);
        // Magic bytes PDF.
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($path));
    }
}
