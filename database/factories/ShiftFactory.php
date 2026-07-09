<?php

namespace Database\Factories;

use App\Models\OrgUnit;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'org_unit_id' => OrgUnit::factory(),
            'nama' => 'Pagi',
            'kode' => strtoupper($this->faker->unique()->lexify('??')),
            'warna' => '#16A34A',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '14:00:00',
            'toleransi_telat' => 10,
            'aktif' => true,
        ];
    }
}
