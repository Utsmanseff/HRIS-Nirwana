<?php

namespace Database\Factories;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalFactory extends Factory
{
    protected $model = Jadwal::class;

    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'tanggal' => now()->toDateString(),
            'shift_id' => Shift::factory(),
            'dibuat_oleh' => null,
        ];
    }
}
