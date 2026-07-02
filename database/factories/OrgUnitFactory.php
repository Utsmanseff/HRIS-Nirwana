<?php

// database/factories/OrgUnitFactory.php

namespace Database\Factories;

use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgUnitFactory extends Factory
{
    protected $model = OrgUnit::class;

    public function definition(): array
    {
        // Pool nama besar — unique()->randomElement 6 opsi habis saat test membuat banyak karyawan.
        return ['parent_id' => null, 'nama' => $this->faker->unique()->numerify('Divisi ###'), 'tipe' => 'divisi', 'aktif' => true];
    }
}
