<?php

namespace Tests\Feature\Disiplin;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Support\EskalasiSanksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EskalasiSanksiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tanpa_sanksi_aktif_saran_teguran1(): void
    {
        $kar = Karyawan::factory()->create();
        $this->assertSame(TingkatSanksi::Teguran1, EskalasiSanksi::sarankan($kar));
        $this->assertCount(0, EskalasiSanksi::sanksiAktif($kar));
    }

    public function test_teguran1_aktif_saran_teguran2(): void
    {
        $kar = Karyawan::factory()->create();
        SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Teguran1)->create(['karyawan_id' => $kar->id]);
        $this->assertSame(TingkatSanksi::Teguran2, EskalasiSanksi::sarankan($kar));
    }

    public function test_teguran3_aktif_saran_sp1(): void
    {
        $kar = Karyawan::factory()->create();
        SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Teguran3)->create(['karyawan_id' => $kar->id]);
        $this->assertSame(TingkatSanksi::Sp1, EskalasiSanksi::sarankan($kar));
    }

    public function test_sp3_aktif_mentok_tetap_sp3(): void
    {
        $kar = Karyawan::factory()->create();
        SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Sp3)->create(['karyawan_id' => $kar->id]);
        $this->assertSame(TingkatSanksi::Sp3, EskalasiSanksi::sarankan($kar));
    }

    public function test_sanksi_lewat_6_bulan_tidak_dihitung(): void
    {
        Carbon::setTestNow('2026-07-07');
        $kar = Karyawan::factory()->create();
        // Terbit 2025-01-01 → berlaku_sampai 2025-07-01 (lewat) → bersih.
        SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Teguran2, Carbon::parse('2025-01-01'))
            ->create(['karyawan_id' => $kar->id]);
        $this->assertCount(0, EskalasiSanksi::sanksiAktif($kar));
        $this->assertSame(TingkatSanksi::Teguran1, EskalasiSanksi::sarankan($kar));
    }

    public function test_hanya_status_diterbitkan_yang_dihitung(): void
    {
        $kar = Karyawan::factory()->create();
        SanksiDisiplin::factory()->tingkat(TingkatSanksi::Sp2)->create([
            'karyawan_id' => $kar->id, 'status' => StatusSanksi::Diajukan,
        ]);
        $this->assertSame(TingkatSanksi::Teguran1, EskalasiSanksi::sarankan($kar));
    }
}
