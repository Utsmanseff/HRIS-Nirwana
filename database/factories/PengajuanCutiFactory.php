<?php

// database/factories/PengajuanCutiFactory.php

namespace Database\Factories;

use App\Enums\KodeJenisCuti;
use App\Enums\StatusPengajuanCuti;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengajuanCutiFactory extends Factory
{
    protected $model = PengajuanCuti::class;

    public function definition(): array
    {
        $mulai = now()->addDays($this->faker->numberBetween(1, 20))->startOfDay();

        return [
            'karyawan_id' => Karyawan::factory(),
            // Default cuti tahunan; JenisCutiSeeder harus sudah di-seed di test.
            'jenis_cuti_id' => fn () => JenisCuti::where('kode', KodeJenisCuti::CutiTahunan->value)->value('id')
                ?? JenisCuti::factory()->create(['kode' => KodeJenisCuti::CutiTahunan->value, 'potong_saldo' => true])->id,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => (clone $mulai)->addDays(2),
            'jumlah_hari' => 3,
            'alasan' => $this->faker->sentence(),
            'status' => StatusPengajuanCuti::Diajukan,
        ];
    }

    public function jenis(KodeJenisCuti $kode): static
    {
        return $this->state(fn () => [
            'jenis_cuti_id' => JenisCuti::where('kode', $kode->value)->value('id'),
        ]);
    }

    public function status(StatusPengajuanCuti $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    /** Rentang + jumlah_hari eksplisit. */
    public function rentang(\DateTimeInterface|string $mulai, \DateTimeInterface|string $selesai, int $hari): static
    {
        return $this->state(fn () => [
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'jumlah_hari' => $hari,
        ]);
    }
}
