<?php

namespace Database\Factories;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use Illuminate\Database\Eloquent\Factories\Factory;

class SanksiDisiplinFactory extends Factory
{
    protected $model = SanksiDisiplin::class;

    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'pengusul_id' => Karyawan::factory(),
            'tingkat' => TingkatSanksi::Teguran1,
            'uraian' => $this->faker->sentence(),
            'tanggal_kejadian' => now()->subDays($this->faker->numberBetween(1, 10))->toDateString(),
            'status' => StatusSanksi::Diajukan,
        ];
    }

    public function tingkat(TingkatSanksi $t): static
    {
        return $this->state(fn () => ['tingkat' => $t]);
    }

    /** Sanksi sudah diterbitkan; berlaku 6 bulan sejak terbit. */
    public function diterbitkan(TingkatSanksi $t = TingkatSanksi::Teguran1, ?\DateTimeInterface $terbit = null): static
    {
        return $this->state(function () use ($t, $terbit) {
            $tgl = $terbit ? \Illuminate\Support\Carbon::parse($terbit) : now();

            return [
                'tingkat' => $t,
                'status' => StatusSanksi::Diterbitkan,
                'nomor_surat' => '01.'.$this->faker->unique()->numberBetween(100, 999).'/HRD/RSUN/VII/2026',
                'tanggal_terbit' => $tgl->toDateString(),
                'berlaku_sampai' => $tgl->copy()->addMonths(6)->toDateString(),
            ];
        });
    }
}
