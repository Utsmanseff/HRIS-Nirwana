<?php

namespace Database\Seeders;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\Karyawan;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTiketSeeder extends Seeder
{
    public function run(): void
    {
        $pembuat = User::first();
        if (! $pembuat) {
            return;
        }
        $pelapor = Karyawan::inRandomOrder()->first();

        $urut = 1;
        foreach (TimTeknis::cases() as $tim) {
            $aset = Aset::whereHas('kategori', fn ($k) => $k->where('tim', $tim->value))->first();

            // Tiket baru (masuk antrian, tertaut aset).
            Tiket::updateOrCreate(
                ['nomor' => sprintf('TKT-%d-%04d', now()->year, $urut++)],
                [
                    'jenis' => JenisTiket::Perbaikan->value,
                    'tim' => $tim->value,
                    'inventaris_id' => $aset?->id,
                    'judul' => 'Perbaikan '.($aset?->nama ?? $tim->label()),
                    'deskripsi' => 'Contoh tiket demo untuk tim '.$tim->label().'.',
                    'pelapor_id' => $pelapor?->id,
                    'unit_pelapor' => $pelapor?->orgUnit?->nama,
                    'dibuat_oleh' => $pembuat->id,
                    'prioritas' => PrioritasTiket::Sedang->value,
                    'status' => StatusTiket::Baru->value,
                    'waktu_lapor' => now()->subDays(2),
                ],
            );

            // Tiket selesai (untuk metrik laporan).
            Tiket::updateOrCreate(
                ['nomor' => sprintf('TKT-%d-%04d', now()->year, $urut++)],
                [
                    'jenis' => JenisTiket::Perbaikan->value,
                    'tim' => $tim->value,
                    'inventaris_id' => null,
                    'judul' => 'Tiket selesai '.$tim->label(),
                    'deskripsi' => 'Sudah ditangani.',
                    'pelapor_id' => null,
                    'dibuat_oleh' => $pembuat->id,
                    'prioritas' => PrioritasTiket::Rendah->value,
                    'status' => StatusTiket::Selesai->value,
                    'waktu_lapor' => now()->subDays(5),
                    'waktu_respon' => now()->subDays(5)->addMinutes(30),
                    'waktu_selesai' => now()->subDays(5)->addHours(2),
                    'penyelesai_id' => $pembuat->id,
                    'catatan_penyelesaian' => 'Selesai (demo).',
                ],
            );
        }
    }
}
