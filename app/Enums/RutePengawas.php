<?php

namespace App\Enums;

/**
 * Rute usulan sanksi bagi pemegang jabatan pengawas — jabatan yang boleh mengusulkan
 * SP untuk SELURUH karyawan, bukan hanya bawahannya sendiri.
 *
 * Sengaja dipilih eksplisit per jabatan, bukan disimpulkan dari level. SPI setara Kabid
 * hari ini, tapi menyandarkan rute pada level berarti SPI berlevel koordinator kelak
 * diam-diam dirutekan lain tanpa ada yang mengubah apa pun.
 */
enum RutePengawas: string
{
    /** Usulan langsung ke HRD, melewati koordinator & kabid unit terdakwa (pola SPI). */
    case LangsungHrd = 'langsung_hrd';

    /** Usulan masuk lewat garis komando karyawan yang diusulkan (pola supervisor). */
    case LewatAtasan = 'lewat_atasan';

    public function label(): string
    {
        return match ($this) {
            self::LangsungHrd => 'Pengawas — usulan langsung ke HRD',
            self::LewatAtasan => 'Pengawas — usulan lewat atasan karyawan terkait',
        };
    }

    public function ringkas(): string
    {
        return match ($this) {
            self::LangsungHrd => 'Pengawas · langsung HRD',
            self::LewatAtasan => 'Pengawas · lewat atasan',
        };
    }
}
