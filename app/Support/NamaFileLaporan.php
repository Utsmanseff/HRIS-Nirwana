<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NamaFileLaporan
{
    /**
     * Rangkai nama file laporan: base + token filter (di-slug) + tanggal-cetak.
     * Token kosong/null diabaikan.
     *
     * Contoh: buat('daftar-karyawan', ['aktif', 'Unit Farmasi'], 'pdf')
     *   → "daftar-karyawan_aktif_unit-farmasi_20260704-1532.pdf"
     */
    public static function buat(string $base, array $tokens, string $ext): string
    {
        $bagian = [$base];
        foreach ($tokens as $t) {
            $t = trim((string) $t);
            if ($t !== '') {
                $bagian[] = Str::slug($t);
            }
        }
        $bagian[] = Carbon::now()->format('Ymd-Hi');

        return implode('_', $bagian).'.'.$ext;
    }
}
