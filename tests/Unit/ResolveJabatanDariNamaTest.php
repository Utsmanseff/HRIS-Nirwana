<?php

namespace Tests\Unit;

use App\Enums\JabatanLevel;
use App\Models\Jabatan;
use App\Models\OrgUnit;
use App\Support\ResolveJabatanDariNama;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveJabatanDariNamaTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_creates_org_unit_and_jabatan(): void
    {
        $jabatan = ResolveJabatanDariNama::resolve('Manajemen', 'Direktur');

        $this->assertInstanceOf(Jabatan::class, $jabatan);
        $this->assertSame('Direktur', $jabatan->nama);
        $this->assertSame('Manajemen', $jabatan->orgUnit->nama);
        $this->assertSame(JabatanLevel::Direktur, $jabatan->level);

        $this->assertDatabaseHas('org_units', ['nama' => 'Manajemen']);
        $this->assertDatabaseHas('jabatan', ['nama' => 'Direktur']);
    }

    public function test_resolve_deduplicates_different_casing_and_spaces(): void
    {
        $jab1 = ResolveJabatanDariNama::resolve('Cleaning Service', 'Cleaning Service');
        $jab2 = ResolveJabatanDariNama::resolve('Cleaning service ', ' Cleaning Service');

        $this->assertSame($jab1->id, $jab2->id);
        $this->assertSame($jab1->org_unit_id, $jab2->org_unit_id);
        $this->assertSame(1, OrgUnit::where('nama', 'Cleaning Service')->count());
        $this->assertSame(1, Jabatan::where('nama', 'Cleaning Service')->count());
    }

    public function test_tebak_level_correctly_guesses_levels(): void
    {
        $this->assertSame(
            JabatanLevel::Direktur,
            ResolveJabatanDariNama::tebakLevel('Direktur Utama'),
        );

        $this->assertSame(
            JabatanLevel::Kabid,
            ResolveJabatanDariNama::tebakLevel('Kabid Penunjang'),
        );

        $this->assertSame(
            JabatanLevel::Kabid,
            ResolveJabatanDariNama::tebakLevel('Kabag Umum & Perlengkapan'),
        );

        $this->assertSame(
            JabatanLevel::Koordinator,
            ResolveJabatanDariNama::tebakLevel('Koordinator Driver'),
        );

        $this->assertSame(
            JabatanLevel::Koordinator,
            ResolveJabatanDariNama::tebakLevel('Kood IT'),
        );

        $this->assertSame(
            JabatanLevel::Koordinator,
            ResolveJabatanDariNama::tebakLevel('Ka. IPSRS'),
        );

        $this->assertSame(
            JabatanLevel::Staff,
            ResolveJabatanDariNama::tebakLevel('Perawat'),
        );

        $this->assertSame(
            JabatanLevel::Staff,
            ResolveJabatanDariNama::tebakLevel('Bidan'),
        );
    }
}
