<?php

namespace Database\Seeders;

use App\Enums\OrgUnitTipe;
use App\Models\Absensi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengaturanAbsensi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data uji laporan absensi: 20 karyawan di 4 unit, absensi acak 1–30 Juli 2026.
 * Jalankan: php artisan db:seed --class=DemoRekapAbsensiSeeder
 */
class DemoRekapAbsensiSeeder extends Seeder
{
    public function run(): void
    {
        $p = PengaturanAbsensi::ambil();
        $namaUnit = ['Farmasi', 'IT', 'IGD', 'Keperawatan'];

        foreach ($namaUnit as $nama) {
            $unit = OrgUnit::where('nama', $nama)->first()
                ?? OrgUnit::create(['nama' => $nama, 'tipe' => OrgUnitTipe::Unit->value]);

            $jab = Jabatan::firstOrCreate(
                ['nama' => 'Staff Uji '.$nama, 'org_unit_id' => $unit->id],
                ['level' => 1],
            );

            $kars = Karyawan::factory()->count(5)->create([
                'org_unit_id' => $unit->id,
                'jabatan_id' => $jab->id,
            ]);

            foreach ($kars as $kar) {
                $this->buatAbsensi($kar, $p);
            }
        }

        $this->command->info('Seed selesai: 20 karyawan × absensi 1–30 Juli 2026.');
    }

    private function buatAbsensi(Karyawan $kar, PengaturanAbsensi $p): void
    {
        $awal = Carbon::create(2026, 7, 1);

        for ($i = 0; $i < 30; $i++) {
            $tgl = $awal->copy()->addDays($i);
            if ($tgl->isSunday()) {
                continue; // libur mingguan
            }
            if (rand(1, 100) <= 8) {
                continue; // ~8% tidak hadir (tak ada baris)
            }

            // Jam masuk 06:50–08:15 (shift Pagi mulai 07:00, toleransi 15).
            $masuk = $tgl->copy()->setTime(7, 0)->addMinutes(rand(-10, 75));
            $batas = $tgl->copy()->setTime(7, 15);
            $telat = $masuk->greaterThan($batas) ? (int) $tgl->copy()->setTime(7, 0)->diffInMinutes($masuk) : 0;

            $anomali = rand(1, 100) <= 5; // ~5% lupa absen pulang
            $pulang = null;
            $pulangCepat = null;
            if (! $anomali) {
                // Jam pulang 13:20–16:00 (shift selesai 14:00).
                $pulang = $tgl->copy()->setTime(14, 0)->addMinutes(rand(-40, 120));
                $selesai = $tgl->copy()->setTime(14, 0);
                $pulangCepat = $pulang->lessThan($selesai) ? (int) $pulang->diffInMinutes($selesai) : 0;
            }

            Absensi::create([
                'karyawan_id' => $kar->id,
                'tanggal_kerja' => $tgl->toDateString(),
                'shift_nama' => 'Pagi',
                'shift_mulai' => '07:00',
                'shift_selesai' => '14:00',
                'shift_toleransi' => 15,
                'jam_masuk' => $masuk,
                'foto_masuk_path' => null,
                'lat_masuk' => (float) $p->office_lat + (rand(-4, 4) / 100000),
                'long_masuk' => (float) $p->office_long + (rand(-4, 4) / 100000),
                'akurasi_masuk' => rand(5, 20),
                'wajah_verif_masuk' => rand(1, 100) > 10, // ~10% fallback wajah
                'telat_menit' => $telat,
                'jam_pulang' => $pulang,
                'lat_pulang' => $pulang ? (float) $p->office_lat : null,
                'long_pulang' => $pulang ? (float) $p->office_long : null,
                'akurasi_pulang' => $pulang ? rand(5, 20) : null,
                'wajah_verif_pulang' => $pulang ? true : null,
                'pulang_cepat_menit' => $pulangCepat,
            ]);
        }
    }
}
