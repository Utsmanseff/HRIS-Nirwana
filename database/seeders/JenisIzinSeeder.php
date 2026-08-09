<?php

namespace Database\Seeders;

use App\Enums\KodeJenisIzin;
use App\Models\JenisIzin;
use Illuminate\Database\Seeder;

class JenisIzinSeeder extends Seeder
{
    public function run(): void
    {
        // ambang_hari HANYA diisi saat baris pertama dibuat — nilai itu boleh disetel HRD,
        // menimpanya tiap seed akan diam-diam mengembalikan setelan mereka.
        // Default 90 hari (±3 bulan) untuk semua jenis: perpanjangan STR/SIP lewat organisasi
        // profesi & dinas makan waktu berbulan-bulan, H-30 kesiangan.
        $baris = [
            [KodeJenisIzin::Str, 90],
            [KodeJenisIzin::Sip, 90],
            [KodeJenisIzin::Sik, 90],
            [KodeJenisIzin::Sertifikat, 90],
        ];

        foreach ($baris as [$kode, $ambang]) {
            JenisIzin::firstOrCreate(
                ['kode' => $kode->value],
                ['nama' => $kode->label(), 'ambang_hari' => $ambang, 'aktif' => true],
            );
        }
    }
}
