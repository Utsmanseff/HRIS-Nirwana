<?php

// database/factories/PenyesuaianSaldoFactory.php

namespace Database\Factories;

use App\Models\Karyawan;
use App\Models\PenyesuaianSaldo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenyesuaianSaldoFactory extends Factory
{
    protected $model = PenyesuaianSaldo::class;

    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'periode_mulai' => now()->startOfDay(),
            'delta' => $this->faker->numberBetween(-3, 3),
            'alasan' => $this->faker->sentence(),
            'dibuat_oleh' => User::factory(),
        ];
    }
}
