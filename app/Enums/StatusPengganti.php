<?php

namespace App\Enums;

enum StatusPengganti: string
{
    case Aktif = 'aktif';
    case Usulan = 'usulan';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Usulan => 'Menunggu Acc',
        };
    }
}
