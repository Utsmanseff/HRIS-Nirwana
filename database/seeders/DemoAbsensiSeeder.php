<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\OrgUnit;
use App\Models\PengaturanAbsensi;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class DemoAbsensiSeeder extends Seeder
{
    public function run(): void
    {
        PengaturanAbsensi::ambil();

        $igd = OrgUnit::where('nama', 'IGD')->first();
        if (! $igd) {
            return; // butuh DemoSdmSeeder lebih dulu
        }

        // Shift IGD (pelayanan) — kode diketik di grid.
        $pagi = Shift::create(['org_unit_id' => $igd->id, 'nama' => 'Pagi', 'kode' => 'P', 'warna' => '#16A34A', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00', 'toleransi_telat' => 15]);
        $siang = Shift::create(['org_unit_id' => $igd->id, 'nama' => 'Siang', 'kode' => 'SI', 'warna' => '#2563EB', 'jam_mulai' => '14:00:00', 'jam_selesai' => '21:00:00', 'toleransi_telat' => 15]);
        Shift::create(['org_unit_id' => $igd->id, 'nama' => 'Malam', 'kode' => 'M', 'warna' => '#7C3AED', 'jam_mulai' => '21:00:00', 'jam_selesai' => '07:00:00', 'toleransi_telat' => 20]);

        $staff = $igd->karyawan()->where('status', 'aktif')->get();

        foreach ($staff as $i => $kar) {
            $shift = $i % 2 === 0 ? $pagi : $siang;

            // Jadwal hari ini + sesi absensi selesai (normal).
            Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => now()->toDateString(), 'shift_id' => $shift->id]);

            Absensi::create([
                'karyawan_id' => $kar->id,
                'tanggal_kerja' => now()->toDateString(),
                'shift_id' => $shift->id,
                'shift_nama' => $shift->nama,
                'shift_mulai' => $shift->jam_mulai,
                'shift_selesai' => $shift->jam_selesai,
                'shift_toleransi' => $shift->toleransi_telat,
                'jam_masuk' => now()->setTimeFromTimeString($shift->jam_mulai)->subMinutes(3),
                'foto_masuk_path' => null,
                'lat_masuk' => -6.9147440,
                'long_masuk' => 107.6098100,
                'akurasi_masuk' => 12.0,
                'wajah_verif_masuk' => true,
                'jam_pulang' => now()->setTimeFromTimeString($shift->jam_selesai)->addMinutes(5),
                'foto_pulang_path' => null,
                'lat_pulang' => -6.9147440,
                'long_pulang' => 107.6098100,
                'akurasi_pulang' => 12.0,
                'wajah_verif_pulang' => true,
                'telat_menit' => 0,
                'pulang_cepat_menit' => 0,
            ]);
        }
    }
}
