<?php

namespace App\Enums;

enum StatusAset: string
{
    case Baik = 'baik';
    case Rusak = 'rusak';
    case DalamPerbaikan = 'dalam_perbaikan';
    case Afkir = 'afkir';

    public function label(): string
    {
        return match ($this) {
            self::Baik => 'Baik',
            self::Rusak => 'Rusak',
            self::DalamPerbaikan => 'Dalam Perbaikan',
            self::Afkir => 'Afkir',
        };
    }
}
