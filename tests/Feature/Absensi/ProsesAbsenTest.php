<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Support\ProsesAbsen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class ProsesAbsenTest extends TestCase
{
    use RefreshDatabase;

    private function dataAbsen(Carbon $jam): array
    {
        return [
            'jam' => $jam,
            'foto_path' => 'absensi/x.webp',
            'lat' => -6.9147440,
            'long' => 107.6098100,
            'akurasi' => 12.0,
            'wajah_verif' => true,
            'flag_lokasi' => [],
        ];
    }

    public function test_masuk_tanpa_jadwal_mode_catat(): void
    {
        $kar = Karyawan::factory()->create();
        $sesi = ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-09 08:10:00')));

        $this->assertNull($sesi->shift_id);
        $this->assertNull($sesi->shift_mulai);
        $this->assertNull($sesi->telat_menit);
        $this->assertSame('2026-07-09', $sesi->tanggal_kerja->toDateString());
        $this->assertTrue($sesi->sesiAktif());
    }

    public function test_masuk_dengan_jadwal_snapshot_shift_dan_telat(): void
    {
        $kar = Karyawan::factory()->create();
        $shift = Shift::factory()->create(['nama' => 'Siang', 'jam_mulai' => '14:00:00', 'jam_selesai' => '21:00:00', 'toleransi_telat' => 15]);
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-09', 'shift_id' => $shift->id]);

        $sesi = ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-09 14:18:00')));

        $this->assertSame($shift->id, $sesi->shift_id);
        $this->assertSame('Siang', $sesi->shift_nama);
        $this->assertSame(18, $sesi->telat_menit);
    }

    public function test_tidak_bisa_masuk_dua_kali_saat_sesi_aktif(): void
    {
        $kar = Karyawan::factory()->create();
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-09 08:00:00')));

        $this->expectException(RuntimeException::class);
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-09 08:05:00')));
    }

    public function test_pulang_menutup_sesi_dan_hitung_pulang_cepat(): void
    {
        $kar = Karyawan::factory()->create();
        $shift = Shift::factory()->create(['jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 10]);
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-09', 'shift_id' => $shift->id]);

        $sesi = ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-09 07:00:00')));
        $ditutup = ProsesAbsen::pulang($kar, $this->dataAbsen(Carbon::parse('2026-07-09 13:40:00')));

        $this->assertSame($sesi->id, $ditutup->id);
        $this->assertFalse($ditutup->sesiAktif());
        $this->assertSame(20, $ditutup->pulang_cepat_menit);
    }

    public function test_tidak_bisa_pulang_tanpa_sesi_aktif(): void
    {
        $kar = Karyawan::factory()->create();
        $this->expectException(RuntimeException::class);
        ProsesAbsen::pulang($kar, $this->dataAbsen(Carbon::parse('2026-07-09 14:00:00')));
    }

    public function test_sesi_aktif_mengembalikan_sesi_terbuka(): void
    {
        $kar = Karyawan::factory()->create();
        $this->assertNull(ProsesAbsen::sesiAktif($kar));
        ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-09 08:00:00')));
        $this->assertInstanceOf(Absensi::class, ProsesAbsen::sesiAktif($kar));
    }

    public function test_dua_sesi_sehari_memakai_snapshot_shift_masing_masing(): void
    {
        $unit = \App\Models\OrgUnit::factory()->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $malam = Shift::factory()->for($unit, 'orgUnit')->create([
            'kode' => 'M', 'nama' => 'Malam', 'jam_mulai' => '00:00:00', 'jam_selesai' => '08:00:00', 'toleransi_telat' => 10,
        ]);
        $sore = Shift::factory()->for($unit, 'orgUnit')->create([
            'kode' => 'S', 'nama' => 'Sore', 'jam_mulai' => '16:00:00', 'jam_selesai' => '00:00:00', 'toleransi_telat' => 10,
        ]);
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20', 'shift_id' => $malam->id]);
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-20', 'shift_id' => $sore->id]);

        $sesi1 = ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 00:05:00')));
        ProsesAbsen::pulang($kar, $this->dataAbsen(Carbon::parse('2026-07-20 08:00:00')));
        $sesi2 = ProsesAbsen::masuk($kar, $this->dataAbsen(Carbon::parse('2026-07-20 16:20:00')));

        $this->assertSame($malam->id, $sesi1->shift_id);
        $this->assertSame(0, $sesi1->telat_menit);          // dalam toleransi 10 menit
        $this->assertSame($sore->id, $sesi2->shift_id);
        $this->assertSame(20, $sesi2->telat_menit);         // 16:20 vs mulai 16:00
    }

    /**
     * catat() adalah satu-satunya pintu dari UI: ia yang memutuskan masuk/pulang
     * DI DALAM kunci per-karyawan, bukan pemanggilnya. Tanpa itu, "cek sesi" dan
     * "tulis baris" jadi dua langkah terpisah dan dua request paralel sama-sama
     * membuat sesi masuk — absen dobel.
     */
    public function test_catat_membuka_sesi_lalu_menutupnya(): void
    {
        $kar = Karyawan::factory()->create();

        $masuk = ProsesAbsen::catat($kar, $this->dataAbsen(Carbon::parse('2026-07-09 08:00:00')));
        $this->assertNull($masuk->jam_pulang);

        $pulang = ProsesAbsen::catat($kar, $this->dataAbsen(Carbon::parse('2026-07-09 16:00:00')));
        $this->assertSame($masuk->id, $pulang->id);
        $this->assertNotNull($pulang->jam_pulang);
        $this->assertSame(1, Absensi::where('karyawan_id', $kar->id)->count());
    }

    /**
     * Kunci yang tak kebagian giliran harus jadi pesan yang bisa dibaca orang,
     * bukan LockTimeoutException yang naik jadi 500.
     */
    public function test_catat_yang_tak_dapat_kunci_melempar_pesan_ramah(): void
    {
        $kar = Karyawan::factory()->create();
        $kunci = Cache::lock("absen-catat:{$kar->id}", 30);
        $this->assertTrue($kunci->get());

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('masih diproses');
            ProsesAbsen::catat($kar, $this->dataAbsen(Carbon::parse('2026-07-09 08:00:00')));
        } finally {
            $kunci->release();
        }
    }
}
