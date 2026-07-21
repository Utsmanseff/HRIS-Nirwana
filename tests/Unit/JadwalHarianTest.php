<?php

namespace Tests\Unit;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\Shift;
use App\Support\JadwalHarian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JadwalHarianTest extends TestCase
{
    use RefreshDatabase;

    private OrgUnit $unit;

    private Karyawan $kar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unit = OrgUnit::factory()->create();
        $this->kar = Karyawan::factory()->create(['org_unit_id' => $this->unit->id]);
    }

    private function shift(string $kode, string $mulai, string $selesai): Shift
    {
        return Shift::factory()->for($this->unit, 'orgUnit')->create([
            'kode' => $kode, 'nama' => "Shift {$kode}",
            'jam_mulai' => $mulai, 'jam_selesai' => $selesai,
        ]);
    }

    private function jadwal(Shift $shift, string $tanggal = '2026-07-20'): Jadwal
    {
        return Jadwal::create(['karyawan_id' => $this->kar->id, 'tanggal' => $tanggal, 'shift_id' => $shift->id]);
    }

    public function test_untuk_mengembalikan_semua_jadwal_hari_itu_urut_jam_mulai(): void
    {
        $sore = $this->shift('S', '16:00:00', '00:00:00');
        $malam = $this->shift('M', '00:00:00', '08:00:00');
        $this->jadwal($sore);
        $this->jadwal($malam);
        $this->jadwal($this->shift('P', '07:00:00', '14:00:00'), '2026-07-21');   // hari lain, diabaikan

        $hasil = JadwalHarian::untuk($this->kar, Carbon::parse('2026-07-20'));

        $this->assertSame(['M', 'S'], $hasil->map(fn ($j) => $j->shift->kode)->all());
    }

    public function test_untuk_menerima_tanggal_string(): void
    {
        $this->jadwal($this->shift('P', '07:00:00', '14:00:00'));

        $this->assertCount(1, JadwalHarian::untuk($this->kar, '2026-07-20'));
    }

    public function test_untuk_kosong_bila_tak_ada_jadwal(): void
    {
        $this->assertTrue(JadwalHarian::untuk($this->kar, '2026-07-20')->isEmpty());
    }

    public function test_jarak_melingkar_memutar_tengah_malam(): void
    {
        // 23:40 (1420) ke 00:00 (0) = 20 menit, bukan 1420.
        $this->assertSame(20, JadwalHarian::jarakMelingkar(1420, 0));
        $this->assertSame(60, JadwalHarian::jarakMelingkar(420, 480));
        $this->assertSame(0, JadwalHarian::jarakMelingkar(600, 600));
    }

    public function test_rentang_shift_lintas_tengah_malam_didorong_sehari(): void
    {
        $this->assertSame([960, 1440], JadwalHarian::rentang($this->shift('S', '16:00:00', '00:00:00')));
        $this->assertSame([420, 840], JadwalHarian::rentang($this->shift('P', '07:00:00', '14:00:00')));
    }
}
