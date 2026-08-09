<?php

namespace Database\Factories;

use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

class IzinKaryawanFactory extends Factory
{
    protected $model = IzinKaryawan::class;

    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            // Pakai jenis yang sudah ada bila ada — kode jenis izin unique dan pool-nya
            // cuma 4, jadi JenisIzin::factory() pasti bentrok begitu seeder sudah jalan.
            'jenis_izin_id' => JenisIzin::query()->value('id') ?? JenisIzin::factory(),
            'nomor' => $this->faker->numerify('##/IZIN/####'),
            'berlaku_mulai' => now()->subYears(2)->toDateString(),
            'berlaku_akhir' => now()->addYears(3)->toDateString(),
        ];
    }
}
