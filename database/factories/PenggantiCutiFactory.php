<?php

namespace Database\Factories;

use App\Enums\StatusPengganti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenggantiCutiFactory extends Factory
{
    protected $model = PenggantiCuti::class;

    public function definition(): array
    {
        return [
            'pengajuan_cuti_id' => PengajuanCuti::factory(),
            'karyawan_id' => Karyawan::factory(),
            'tanggal_mulai' => now()->addDays(1)->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'status' => StatusPengganti::Aktif,
            'dibuat_oleh' => null,
        ];
    }

    public function usulan(): static
    {
        return $this->state(fn () => ['status' => StatusPengganti::Usulan]);
    }
}
