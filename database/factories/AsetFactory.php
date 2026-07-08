<?php

namespace Database\Factories;

use App\Enums\StatusAset;
use App\Models\Aset;
use App\Models\KategoriInventaris;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsetFactory extends Factory
{
    protected $model = Aset::class;

    public function definition(): array
    {
        return [
            'kode' => strtoupper($this->faker->unique()->bothify('AST-####')),
            'nama' => $this->faker->words(2, true),
            'kategori_inventaris_id' => KategoriInventaris::factory(),
            'merk' => $this->faker->company(),
            'model' => $this->faker->bothify('MD-###'),
            'no_seri' => $this->faker->unique()->bothify('SN-#####'),
            'tanggal_pengadaan' => $this->faker->dateTimeBetween('-4 years', 'now'),
            'nilai_perolehan' => $this->faker->numberBetween(500000, 50000000),
            'org_unit_id' => null,
            'penanggung_jawab_id' => null,
            'status' => StatusAset::Baik->value,
            'keterangan' => null,
        ];
    }
}
