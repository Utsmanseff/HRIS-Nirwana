<?php

namespace Database\Seeders;

use App\Enums\KodeJenisCuti;
use App\Models\JenisCuti;
use Illuminate\Database\Seeder;

class JenisCutiSeeder extends Seeder
{
    public function run(): void
    {
        $baris = [
            [KodeJenisCuti::CutiTahunan, 'Cuti Tahunan', true, null, false, false],
            [KodeJenisCuti::IzinBiasa, 'Izin Biasa', false, 'potong_gaji_jasa', true, false],
            [KodeJenisCuti::CutiSakit, 'Cuti Sakit', false, null, true, true],
            [KodeJenisCuti::CutiMelahirkan, 'Cuti Melahirkan', false, null, true, true],
        ];

        foreach ($baris as [$kode, $nama, $potong, $efek, $lampiran, $backdate]) {
            JenisCuti::updateOrCreate(
                ['kode' => $kode->value],
                [
                    'nama' => $nama,
                    'potong_saldo' => $potong,
                    'efek_penggajian' => $efek,
                    'butuh_lampiran' => $lampiran,
                    'boleh_backdate' => $backdate,
                    'aktif' => true,
                ],
            );
        }
    }
}
