<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Support\RekapAbsensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapAbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_derived(): void
    {
        $kar = Karyawan::factory()->create();

        $normal = Absensi::factory()->create(['karyawan_id' => $kar->id, 'telat_menit' => 0, 'pulang_cepat_menit' => 0]);
        $telat = Absensi::factory()->create(['karyawan_id' => $kar->id, 'telat_menit' => 10]);
        $nyangkut = Absensi::factory()->create([
            'karyawan_id' => $kar->id, 'tanggal_kerja' => now()->subDays(3)->toDateString(), 'jam_pulang' => null,
        ]);

        $this->assertSame('normal', $normal->statusRekap());
        $this->assertSame('telat', $telat->statusRekap());
        $this->assertSame('anomali', $nyangkut->statusRekap());
    }

    public function test_query_filter_periode(): void
    {
        $kar = Karyawan::factory()->create();
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => '2026-06-30']);
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => '2026-07-05']);

        $hasil = RekapAbsensi::query(['dari' => '2026-06-30', 'sampai' => '2026-06-30'])->get();
        $this->assertCount(1, $hasil);
    }

    public function test_filter_status_derived(): void
    {
        $kar = Karyawan::factory()->create();
        $hari = '2026-06-30';
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => $hari, 'telat_menit' => 0]);
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => $hari, 'telat_menit' => 15]);

        $telat = RekapAbsensi::ambil(['dari' => $hari, 'sampai' => $hari, 'status' => 'telat']);
        $this->assertCount(1, $telat);
        $this->assertSame('telat', $telat->first()->statusRekap());
    }

    public function test_statistik_menghitung_hadir_telat_anomali(): void
    {
        $kar = Karyawan::factory()->create();
        $hari = '2026-06-30';
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => $hari, 'telat_menit' => 0]);
        Absensi::factory()->create(['karyawan_id' => $kar->id, 'tanggal_kerja' => $hari, 'telat_menit' => 15]);

        $stat = RekapAbsensi::statistik(['dari' => $hari, 'sampai' => $hari]);
        $this->assertSame(2, $stat['hadir']);
        $this->assertSame(1, $stat['telat']);
    }
}
