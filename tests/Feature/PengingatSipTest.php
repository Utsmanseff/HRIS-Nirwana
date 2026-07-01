<?php

namespace Tests\Feature;

use App\Enums\SeverityPengingat;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Support\PengingatSip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengingatSipTest extends TestCase
{
    use RefreshDatabase;

    public function test_sip_20_hari_lagi_akan_berakhir(): void
    {
        Karyawan::factory()->create([
            'status' => StatusKaryawan::Aktif,
            'sip_nomor' => 'SIP/1/2026',
            'sip_berlaku_akhir' => now()->addDays(20),
        ]);
        $list = PengingatSip::semua();
        $this->assertCount(1, $list);
        $this->assertSame(SeverityPengingat::AkanBerakhir, $list->first()->severity);
    }

    public function test_sip_lewat_jadi_terlewat(): void
    {
        Karyawan::factory()->create([
            'status' => StatusKaryawan::Aktif,
            'sip_nomor' => 'SIP/2/2026',
            'sip_berlaku_akhir' => now()->subDays(3),
        ]);
        $this->assertSame(SeverityPengingat::Terlewat, PengingatSip::semua()->first()->severity);
    }

    public function test_sip_masih_lama_tanpa_pengingat(): void
    {
        Karyawan::factory()->create([
            'status' => StatusKaryawan::Aktif,
            'sip_nomor' => 'SIP/3/2026',
            'sip_berlaku_akhir' => now()->addYears(2),
        ]);
        $this->assertCount(0, PengingatSip::semua());
    }

    public function test_karyawan_tanpa_sip_diabaikan(): void
    {
        Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]); // sip null
        $this->assertCount(0, PengingatSip::semua());
    }

    public function test_karyawan_nonaktif_gugur(): void
    {
        Karyawan::factory()->create([
            'status' => StatusKaryawan::Nonaktif,
            'sip_nomor' => 'SIP/4/2026',
            'sip_berlaku_akhir' => now()->subDays(3),
        ]);
        $this->assertCount(0, PengingatSip::semua());
    }
}
