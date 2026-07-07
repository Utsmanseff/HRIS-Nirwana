<?php

namespace App\Enums;

enum StatusSanksi: string
{
    case Diajukan = 'diajukan';
    case Diproses = 'diproses';
    case Diterbitkan = 'diterbitkan';
    case Ditolak = 'ditolak';
    case Dicabut = 'dicabut';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Diproses => 'Diproses',
            self::Diterbitkan => 'Diterbitkan',
            self::Ditolak => 'Ditolak',
            self::Dicabut => 'Dicabut',
        };
    }

    /** Masih dalam proses approval (belum final). */
    public function pending(): bool
    {
        return $this === self::Diajukan || $this === self::Diproses;
    }
}
