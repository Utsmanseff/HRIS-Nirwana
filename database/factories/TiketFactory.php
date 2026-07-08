<?php

namespace Database\Factories;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TiketFactory extends Factory
{
    protected $model = Tiket::class;

    public function definition(): array
    {
        return [
            'nomor' => 'TKT-'.now()->year.'-'.$this->faker->unique()->numerify('9###'),
            'jenis' => JenisTiket::Perbaikan,
            'tim' => TimTeknis::It,
            'inventaris_id' => null,
            'jadwal_pemeliharaan_id' => null,
            'judul' => $this->faker->sentence(4),
            'deskripsi' => $this->faker->paragraph(),
            'pelapor_id' => null,
            'unit_pelapor' => null,
            'dibuat_oleh' => User::factory(),
            'prioritas' => PrioritasTiket::Sedang,
            'status' => StatusTiket::Baru,
            'waktu_lapor' => now(),
            'waktu_respon' => null,
            'waktu_selesai' => null,
            'penyelesai_id' => null,
            'catatan_penyelesaian' => null,
        ];
    }

    public function jenis(JenisTiket $j): static
    {
        return $this->state(fn () => ['jenis' => $j]);
    }

    public function tim(TimTeknis $t): static
    {
        return $this->state(fn () => ['tim' => $t]);
    }

    public function status(StatusTiket $s): static
    {
        return $this->state(fn () => ['status' => $s]);
    }

    public function prioritas(PrioritasTiket $p): static
    {
        return $this->state(fn () => ['prioritas' => $p]);
    }
}
