<?php

namespace App\Enums;

enum StatusTiket: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Batal => 'Batal',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Baru => 'badge-info',
            self::Diproses => 'badge-warning',
            self::Selesai => 'badge-success',
            self::Batal => 'badge-neutral',
        };
    }

    /** @return list<self> status yang masih di antrian. */
    public static function aktif(): array
    {
        return [self::Baru, self::Diproses];
    }
}
