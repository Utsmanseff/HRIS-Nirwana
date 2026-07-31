<?php

namespace App\Support;

use App\Enums\JabatanLevel;
use App\Enums\OrgUnitTipe;
use App\Models\Jabatan;
use App\Models\OrgUnit;

class ResolveJabatanDariNama
{
    public static function resolve(string $unitKerja, string $jabatan): Jabatan
    {
        $unitNama = static::normalize($unitKerja);
        $jabatanNama = static::normalize($jabatan);

        if ($unitNama === '') {
            $unitNama = 'Lain-lain';
        }
        if ($jabatanNama === '') {
            $jabatanNama = 'Staff';
        }

        // Case-insensitive lookup or create OrgUnit
        $unit = OrgUnit::whereRaw('LOWER(nama) = ?', [mb_strtolower($unitNama)])->first();
        if (! $unit) {
            $unit = OrgUnit::create([
                'nama' => $unitNama,
                'tipe' => OrgUnitTipe::Unit,
                'aktif' => true,
            ]);
        }

        $level = static::tebakLevel($jabatanNama);

        // Case-insensitive lookup or create Jabatan
        $jab = Jabatan::where('org_unit_id', $unit->id)
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($jabatanNama)])
            ->first();

        if (! $jab) {
            $jab = Jabatan::create([
                'nama' => $jabatanNama,
                'org_unit_id' => $unit->id,
                'level' => $level,
                'aktif' => true,
            ]);
        }

        return $jab;
    }

    public static function tebakLevel(string $namaJabatan): JabatanLevel
    {
        $nama = mb_strtolower($namaJabatan);

        if (str_contains($nama, 'direktur')) {
            return JabatanLevel::Direktur;
        }

        if (
            str_contains($nama, 'kabid') ||
            str_contains($nama, 'kabag') ||
            str_contains($nama, 'kepala bidang') ||
            str_contains($nama, 'kepala bagian')
        ) {
            return JabatanLevel::Kabid;
        }

        if (
            str_contains($nama, 'koordinator') ||
            str_contains($nama, 'kood') ||
            str_contains($nama, 'ka.') ||
            str_contains($nama, 'kepala unit') ||
            str_contains($nama, 'kepala ruangan') ||
            str_contains($nama, 'karu')
        ) {
            return JabatanLevel::Koordinator;
        }

        return JabatanLevel::Staff;
    }

    public static function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
