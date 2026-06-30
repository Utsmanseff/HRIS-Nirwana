<?php

// database/factories/KontrakFactory.php

namespace Database\Factories;

use App\Models\Karyawan;
use App\Models\Kontrak;
use Illuminate\Database\Eloquent\Factories\Factory;

class KontrakFactory extends Factory
{
    protected $model = Kontrak::class;

    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(), 'jenis' => 'pkwt',
            'tanggal_mulai' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
            'tanggal_akhir' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}
