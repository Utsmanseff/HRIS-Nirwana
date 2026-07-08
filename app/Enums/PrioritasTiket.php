<?php

namespace App\Enums;

enum PrioritasTiket: string
{
    case Rendah = 'rendah';
    case Sedang = 'sedang';
    case Tinggi = 'tinggi';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Rendah',
            self::Sedang => 'Sedang',
            self::Tinggi => 'Tinggi',
            self::Urgent => 'Urgent',
        };
    }

    /** Skor sort (besar = lebih mendesak). */
    public function urutan(): int
    {
        return match ($this) {
            self::Rendah => 1,
            self::Sedang => 2,
            self::Tinggi => 3,
            self::Urgent => 4,
        };
    }

    /** Kelas badge (pola theme.css). */
    public function badge(): string
    {
        return match ($this) {
            self::Rendah => 'badge-neutral',
            self::Sedang => 'badge-info',
            self::Tinggi => 'badge-warning',
            self::Urgent => 'badge-danger',
        };
    }
}
