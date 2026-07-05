<?php

// database/factories/JenisCutiFactory.php

namespace Database\Factories;

use App\Enums\KodeJenisCuti;
use App\Models\JenisCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

class JenisCutiFactory extends Factory
{
    protected $model = JenisCuti::class;

    public function definition(): array
    {
        return [
            'kode' => KodeJenisCuti::CutiTahunan->value,
            'nama' => 'Cuti Tahunan',
            'potong_saldo' => true,
            'efek_penggajian' => null,
            'butuh_lampiran' => false,
            'boleh_backdate' => false,
            'aktif' => true,
        ];
    }
}
