<?php

namespace Tests\Unit;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Support\RekapDisiplin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapDisiplinTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_filter_periode_dan_status(): void
    {
        $kar = Karyawan::factory()->create();
        $dalam = SanksiDisiplin::factory()->create([
            'karyawan_id' => $kar->id,
            'status' => StatusSanksi::Diterbitkan,
            'tanggal_kejadian' => '2026-03-10',
        ]);
        $luar = SanksiDisiplin::factory()->create([
            'karyawan_id' => $kar->id,
            'tanggal_kejadian' => '2025-12-01',
        ]);

        $hasil = RekapDisiplin::daftarSanksi([
            'dari' => '2026-01-01', 'sampai' => '2026-12-31',
            'status' => StatusSanksi::Diterbitkan->value,
        ]);

        $this->assertTrue($hasil->contains('id', $dalam->id));
        $this->assertFalse($hasil->contains('id', $luar->id));
    }

    public function test_filter_unit_termasuk_turunan(): void
    {
        $induk = OrgUnit::factory()->create(['tipe' => 'bidang', 'parent_id' => null]);
        $anak = OrgUnit::factory()->create(['tipe' => 'unit', 'parent_id' => $induk->id]);
        $karAnak = Karyawan::factory()->create(['org_unit_id' => $anak->id]);
        $karLuar = Karyawan::factory()->create(['org_unit_id' => OrgUnit::factory()->create()->id]);
        $s1 = SanksiDisiplin::factory()->create(['karyawan_id' => $karAnak->id, 'tanggal_kejadian' => '2026-05-01']);
        $s2 = SanksiDisiplin::factory()->create(['karyawan_id' => $karLuar->id, 'tanggal_kejadian' => '2026-05-01']);

        $hasil = RekapDisiplin::daftarSanksi(['unit_id' => $induk->id]);

        $this->assertTrue($hasil->contains('id', $s1->id));
        $this->assertFalse($hasil->contains('id', $s2->id));
    }

    public function test_hitung_status_lengkap_default_nol(): void
    {
        $kar = Karyawan::factory()->create();
        SanksiDisiplin::factory()->create(['karyawan_id' => $kar->id, 'status' => StatusSanksi::Diterbitkan, 'tanggal_kejadian' => '2026-04-01']);
        SanksiDisiplin::factory()->create(['karyawan_id' => $kar->id, 'status' => StatusSanksi::Ditolak, 'tanggal_kejadian' => '2026-04-02']);

        $hitung = RekapDisiplin::hitungStatus(['dari' => '2026-01-01', 'sampai' => '2026-12-31', 'status' => StatusSanksi::Diterbitkan->value]);

        $this->assertSame(1, $hitung['diterbitkan']);
        $this->assertSame(1, $hitung['ditolak']); // abaikan filter status
        $this->assertSame(0, $hitung['dicabut']);
    }

    public function test_jumlah_org_wide(): void
    {
        $kar = Karyawan::factory()->create();
        SanksiDisiplin::factory()->create(['karyawan_id' => $kar->id, 'status' => StatusSanksi::Diajukan]);
        SanksiDisiplin::factory()->create(['karyawan_id' => $kar->id, 'status' => StatusSanksi::Diproses]);
        SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Sp1)->create(['karyawan_id' => $kar->id]);

        $this->assertSame(2, RekapDisiplin::jumlahPendingOrgWide());
        $this->assertSame(1, RekapDisiplin::jumlahDiterbitkanOrgWide());
    }
}
