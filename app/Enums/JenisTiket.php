<?php

namespace App\Enums;

enum JenisTiket: string
{
    case Perbaikan = 'perbaikan';
    case Pemeliharaan = 'pemeliharaan';

    public function label(): string
    {
        return match ($this) {
            self::Perbaikan => 'Perbaikan',
            self::Pemeliharaan => 'Pemeliharaan',
        };
    }
}
