<?php

namespace App\Enums;

enum TipePengganti: string
{
    case Cuti = 'cuti';
    case Lowongan = 'lowongan';

    public function label(): string
    {
        return match ($this) {
            self::Cuti => 'Pengganti Cuti',
            self::Lowongan => 'Isi Jadwal Kosong',
        };
    }

    /** Awalan kolom Keterangan laporan absensi; nama yang digantikan menyusul. */
    public function prefiksKeterangan(): string
    {
        return match ($this) {
            self::Cuti => 'Pengganti cuti',
            self::Lowongan => 'Mengisi jadwal kosong',
        };
    }
}
