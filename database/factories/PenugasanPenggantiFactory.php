<?php

namespace Database\Factories;

use App\Enums\StatusPengganti;
use App\Enums\TipePengganti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenugasanPenggantiFactory extends Factory
{
    protected $model = PenugasanPengganti::class;

    public function definition(): array
    {
        return [
            'tipe' => TipePengganti::Cuti,
            'pengajuan_cuti_id' => PengajuanCuti::factory(),
            'karyawan_digantikan_id' => Karyawan::factory(),
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

    /** Lowongan: tanpa pengajuan cuti, rentang terbuka. */
    public function lowongan(): static
    {
        return $this->state(fn () => [
            'tipe' => TipePengganti::Lowongan,
            'pengajuan_cuti_id' => null,
            'tanggal_selesai' => null,
        ]);
    }
}
