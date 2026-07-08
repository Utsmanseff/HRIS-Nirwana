<?php

namespace Database\Factories;

use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalPemeliharaanFactory extends Factory
{
    protected $model = JadwalPemeliharaan::class;

    public function definition(): array
    {
        return [
            'aset_id' => Aset::factory(),
            'nama' => $this->faker->randomElement(['Service rutin', 'Kalibrasi', 'Inspeksi']),
            'interval_bulan' => $this->faker->randomElement([3, 6, 12]),
            'terakhir_dilakukan' => now()->subMonths(2),
            'aktif' => true,
        ];
    }
}
