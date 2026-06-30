<?php

// database/factories/KaryawanFactory.php

namespace Database\Factories;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class KaryawanFactory extends Factory
{
    protected $model = Karyawan::class;

    public function definition(): array
    {
        return [
            'nip' => $this->faker->unique()->numerify('19##.##.##.###'),
            'nama_lengkap' => $this->faker->name(),
            'nik' => $this->faker->numerify('################'),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'no_hp' => $this->faker->numerify('08##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'org_unit_id' => OrgUnit::factory(),
            'jabatan_id' => Jabatan::factory(),
            'atasan_id' => null,
            'tanggal_masuk' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'status' => 'aktif',
        ];
    }
}
