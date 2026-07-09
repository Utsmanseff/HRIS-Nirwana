<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'tanggal_kerja' => now()->toDateString(),
            'shift_id' => null,
            'shift_nama' => null,
            'shift_mulai' => null,
            'shift_selesai' => null,
            'shift_toleransi' => null,
            'jam_masuk' => now()->setTime(7, 0),
            'foto_masuk_path' => 'absensi/1/masuk.webp',
            'lat_masuk' => -6.9147440,
            'long_masuk' => 107.6098100,
            'akurasi_masuk' => 12.0,
            'wajah_verif_masuk' => true,
            'flag_lokasi_masuk' => null,
            'jam_pulang' => now()->setTime(14, 0),
            'foto_pulang_path' => 'absensi/1/pulang.webp',
            'lat_pulang' => -6.9147440,
            'long_pulang' => 107.6098100,
            'akurasi_pulang' => 12.0,
            'wajah_verif_pulang' => true,
            'flag_lokasi_pulang' => null,
            'telat_menit' => null,
            'pulang_cepat_menit' => null,
        ];
    }

    /** Sesi masih aktif (belum pulang). */
    public function aktif(): static
    {
        return $this->state(fn () => [
            'jam_pulang' => null,
            'foto_pulang_path' => null,
            'lat_pulang' => null,
            'long_pulang' => null,
            'akurasi_pulang' => null,
            'wajah_verif_pulang' => null,
        ]);
    }
}
