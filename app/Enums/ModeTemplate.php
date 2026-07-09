<?php

namespace App\Enums;

enum ModeTemplate: string
{
    case Rotasi = 'rotasi';       // siklus panjang-bebas, rotasi kontinu dari jangkar (abai hari)
    case Mingguan = 'mingguan';   // 7 slot Sen–Min, posisi = nama hari (jangkar diabaikan)

    public function label(): string
    {
        return match ($this) {
            self::Rotasi => 'Rotasi',
            self::Mingguan => 'Mingguan',
        };
    }
}
