<?php

namespace Database\Factories;

use App\Enums\KodeJenisIzin;
use App\Models\JenisIzin;
use Illuminate\Database\Eloquent\Factories\Factory;

class JenisIzinFactory extends Factory
{
    protected $model = JenisIzin::class;

    public function definition(): array
    {
        return [
            'kode' => $this->faker->unique()->randomElement(array_column(KodeJenisIzin::cases(), 'value')),
            'nama' => 'Jenis Izin',
            'ambang_hari' => 30,
            'aktif' => true,
        ];
    }
}
