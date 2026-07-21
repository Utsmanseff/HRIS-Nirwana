<?php

namespace Tests\Unit;

use App\Models\Absensi;
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

    private function sesiSelesai(Shift $shift, string $tanggal, string $masuk, string $pulang): Absensi
    {
        return Absensi::create([
            'karyawan_id' => $this->kar->id,
            'tanggal_kerja' => $tanggal,
            'shift_id' => $shift->id,
            'shift_nama' => $shift->nama,
            'shift_mulai' => $shift->jam_mulai,
            'shift_selesai' => $shift->jam_selesai,
            'shift_toleransi' => $shift->toleransi_telat,
            'jam_masuk' => $masuk,
            'jam_pulang' => $pulang,
            'lat_masuk' => -3.31, 'long_masuk' => 114.59, 'akurasi_masuk' => 10,
            'wajah_verif_masuk' => true,
        ]);
    }

    public function test_pilih_untuk_absen_ambil_shift_terdekat(): void
    {
        $malam = $this->shift('M', '00:00:00', '08:00:00');
        $sore = $this->shift('S', '16:00:00', '00:00:00');
        $this->jadwal($malam);
        $this->jadwal($sore);

        $pilih = JadwalHarian::pilihUntukAbsen($this->kar, Carbon::parse('2026-07-20 15:50:00'));

        $this->assertSame($sore->id, $pilih->shift_id);
    }

    public function test_pilih_untuk_absen_memutar_tengah_malam(): void
    {
        $malam = $this->shift('M', '00:00:00', '08:00:00');
        $siang = $this->shift('D', '12:00:00', '20:00:00');
        $this->jadwal($malam);
        $this->jadwal($siang);

        // 23:40 lebih dekat ke 00:00 (20 menit) daripada ke 12:00.
        $pilih = JadwalHarian::pilihUntukAbsen($this->kar, Carbon::parse('2026-07-20 23:40:00'));

        $this->assertSame($malam->id, $pilih->shift_id);
    }

    public function test_pilih_untuk_absen_melewati_shift_yang_sudah_terpakai(): void
    {
        $malam = $this->shift('M', '00:00:00', '08:00:00');
        $sore = $this->shift('S', '16:00:00', '00:00:00');
        $this->jadwal($malam);
        $this->jadwal($sore);
        $this->sesiSelesai($malam, '2026-07-20', '2026-07-20 00:02:00', '2026-07-20 08:05:00');

        // Jam absen dekat ke 00:00, tapi shift malam sudah dipakai → jatuh ke sore.
        $pilih = JadwalHarian::pilihUntukAbsen($this->kar, Carbon::parse('2026-07-20 23:50:00'));

        $this->assertSame($sore->id, $pilih->shift_id);
    }

    public function test_pilih_untuk_absen_null_bila_semua_terpakai(): void
    {
        $pagi = $this->shift('P', '07:00:00', '14:00:00');
        $this->jadwal($pagi);
        $this->sesiSelesai($pagi, '2026-07-20', '2026-07-20 07:00:00', '2026-07-20 14:00:00');

        $this->assertNull(JadwalHarian::pilihUntukAbsen($this->kar, Carbon::parse('2026-07-20 15:00:00')));
    }

    public function test_pilih_untuk_absen_null_bila_tak_ada_jadwal(): void
    {
        $this->assertNull(JadwalHarian::pilihUntukAbsen($this->kar, Carbon::parse('2026-07-20 08:00:00')));
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

    public function test_bentrok_benar_saat_jam_beririsan(): void
    {
        $this->jadwal($this->shift('P', '07:00:00', '14:00:00'));
        $siang = $this->shift('D', '13:00:00', '21:00:00');

        $this->assertTrue(JadwalHarian::bentrok($this->kar, '2026-07-20', $siang));
    }

    public function test_bentrok_salah_saat_hanya_bersentuhan_ujung(): void
    {
        $this->jadwal($this->shift('S', '16:00:00', '00:00:00'));
        $malam = $this->shift('M', '00:00:00', '08:00:00');

        $this->assertFalse(JadwalHarian::bentrok($this->kar, '2026-07-20', $malam));
    }

    public function test_bentrok_salah_untuk_hari_lain(): void
    {
        $this->jadwal($this->shift('P', '07:00:00', '14:00:00'), '2026-07-21');
        $siang = $this->shift('D', '13:00:00', '21:00:00');

        $this->assertFalse(JadwalHarian::bentrok($this->kar, '2026-07-20', $siang));
    }

    public function test_bentrok_bisa_mengabaikan_satu_baris(): void
    {
        $pagi = $this->shift('P', '07:00:00', '14:00:00');
        $baris = $this->jadwal($pagi);

        $this->assertFalse(JadwalHarian::bentrok($this->kar, '2026-07-20', $pagi, $baris->id));
        $this->assertTrue(JadwalHarian::bentrok($this->kar, '2026-07-20', $pagi));
    }
}
