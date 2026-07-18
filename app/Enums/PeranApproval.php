<?php

namespace App\Enums;

enum PeranApproval: string
{
    case Koordinator = 'koordinator';
    case Kabid = 'kabid';
    case Hrd = 'hrd';
    case Direktur = 'direktur';

    public function label(): string
    {
        return match ($this) {
            self::Koordinator => 'Koordinator',
            self::Kabid => 'Kabid',
            self::Hrd => 'HRD',
            self::Direktur => 'Direktur',
        };
    }
}
