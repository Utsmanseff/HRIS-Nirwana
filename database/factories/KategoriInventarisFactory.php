<?php

namespace Database\Factories;

use App\Enums\TimTeknis;
use App\Models\KategoriInventaris;
use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriInventarisFactory extends Factory
{
    protected $model = KategoriInventaris::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->unique()->word(),
            'tim' => TimTeknis::It,
            'aktif' => true,
        ];
    }
}
