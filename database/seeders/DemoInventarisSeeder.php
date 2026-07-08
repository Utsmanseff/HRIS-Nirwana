<?php

namespace Database\Seeders;

use App\Enums\StatusAset;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\KategoriInventaris;
use App\Models\OrgUnit;
use Illuminate\Database\Seeder;

class DemoInventarisSeeder extends Seeder
{
    public function run(): void
    {
        $unitIds = OrgUnit::pluck('id')->all();
        $unit = fn () => empty($unitIds) ? null : $unitIds[array_rand($unitIds)];

        // [prefix kode, [nama kategori...], contoh aset per kategori]
        $peta = [
            TimTeknis::It->value => ['IT', ['PC & Laptop', 'Printer', 'Jaringan']],
            TimTeknis::Sarana->value => ['SAR', ['AC', 'Genset', 'Instalasi Listrik']],
            TimTeknis::Atem->value => ['MED', ['Ventilator', 'Monitor Pasien', 'Alat Bedah']],
        ];

        $urut = 1;
        foreach ($peta as $tim => [$prefix, $namaKategori]) {
            foreach ($namaKategori as $nk) {
                $kat = KategoriInventaris::updateOrCreate(
                    ['nama' => $nk, 'tim' => $tim],
                    ['aktif' => true],
                );

                for ($i = 1; $i <= 2; $i++) {
                    $kode = sprintf('%s-%04d', $prefix, $urut++);
                    $aset = Aset::updateOrCreate(
                        ['kode' => $kode],
                        [
                            'nama' => $nk.' #'.$i,
                            'kategori_inventaris_id' => $kat->id,
                            'merk' => 'Demo',
                            'model' => 'MD-'.$urut,
                            'no_seri' => 'SN-'.$prefix.$urut,
                            'tanggal_pengadaan' => now()->subYears(2),
                            'nilai_perolehan' => 5_000_000,
                            'org_unit_id' => $unit(),
                            'status' => StatusAset::Baik->value,
                        ],
                    );

                    // Jadwal: satu jatuh tempo (untuk demo pengingat), satu jauh.
                    JadwalPemeliharaan::updateOrCreate(
                        ['aset_id' => $aset->id, 'nama' => 'Service rutin'],
                        [
                            'interval_bulan' => 6,
                            'terakhir_dilakukan' => $i === 1 ? now()->subMonths(6)->subDays(3) : now()->subMonth(),
                            'aktif' => true,
                        ],
                    );
                }
            }
        }
    }
}
