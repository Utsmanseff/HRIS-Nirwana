<?php

// app/Enums/JabatanLevel.php

namespace App\Enums;

enum JabatanLevel: int
{
    case Staff = 1;
    case Koordinator = 2;
    case Kabid = 3;
    case Direktur = 4;

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff', self::Koordinator => 'Koordinator',
            self::Kabid => 'Kabid/Kabag', self::Direktur => 'Direktur',
        };
    }
}
