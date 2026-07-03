<?php

// database/factories/OrgUnitFactory.php

namespace Database\Factories;

use App\Enums\OrgUnitTipe;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgUnitFactory extends Factory
{
    protected $model = OrgUnit::class;

    public function definition(): array
    {
        return ['parent_id' => null, 'nama' => $this->faker->unique()->numerify('Unit ###'), 'tipe' => OrgUnitTipe::Unit->value, 'aktif' => true];
    }

    public function direktur(): static
    {
        return $this->state(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
    }

    public function bidang(): static
    {
        return $this->state(['tipe' => OrgUnitTipe::Bidang->value]);
    }

    public function bagian(): static
    {
        return $this->state(['tipe' => OrgUnitTipe::Bagian->value]);
    }
}
