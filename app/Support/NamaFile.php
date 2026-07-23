<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Konvensi nama file untuk SEMUA dokumen yang dihasilkan sistem. */
class NamaFile
{
    /**
     * Nama file laporan: base + token filter (di-slug) + waktu cetak.
     * Laporan = snapshot, jadi waktu unduh ikut dicantumkan.
     *
     * Contoh: laporan('daftar-karyawan', ['aktif', 'Unit Farmasi'], 'pdf')
     *   → "daftar-karyawan_aktif_unit-farmasi_20260704-1532.pdf"
     */
    public static function laporan(string $base, array $tokens, string $ext): string
    {
        return self::rangkai($base, $tokens, Carbon::now()->format('Ymd-Hi'), $ext);
    }

    /**
     * Nama file surat: base + token (mis. nama karyawan) + TANGGAL SURAT.
     * Surat = dokumen tetap, jadi nama tak berubah tiap kali diunduh.
     *
     * Contoh: surat('surat-keterangan-cuti', ['Andi Pelaksana'], $tgl, 'pdf')
     *   → "surat-keterangan-cuti_andi-pelaksana_20260717.pdf"
     */
    public static function surat(string $base, array $tokens, ?Carbon $tanggal, string $ext): string
    {
        return self::rangkai($base, $tokens, ($tanggal ?? Carbon::now())->format('Ymd'), $ext);
    }

    /** Rangkai base + token ter-slug + stempel waktu, dipisah underscore. Token kosong diabaikan. */
    private static function rangkai(string $base, array $tokens, string $stempel, string $ext): string
    {
        $bagian = [$base];
        foreach ($tokens as $t) {
            $t = trim((string) $t);
            if ($t !== '') {
                $bagian[] = Str::slug($t);
            }
        }
        $bagian[] = $stempel;

        return implode('_', $bagian).'.'.$ext;
    }
}
