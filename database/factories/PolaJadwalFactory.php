<?php

namespace Database\Factories;

use App\Models\Karyawan;
use App\Models\PolaJadwal;
use App\Models\TemplateJadwal;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolaJadwalFactory extends Factory
{
    protected $model = PolaJadwal::class;

    public function definition(): array
    {
        return [
            'template_id' => TemplateJadwal::factory(),
            'karyawan_id' => Karyawan::factory(),
            'posisi' => 0,
            'shift_id' => null,
        ];
    }
}
