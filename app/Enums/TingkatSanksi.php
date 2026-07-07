<?php

namespace App\Enums;

enum TingkatSanksi: int
{
    case Teguran1 = 1;
    case Teguran2 = 2;
    case Teguran3 = 3;
    case Sp1 = 4;
    case Sp2 = 5;
    case Sp3 = 6;

    public function label(): string
    {
        return match ($this) {
            self::Teguran1 => 'Teguran 1',
            self::Teguran2 => 'Teguran 2',
            self::Teguran3 => 'Teguran 3',
            self::Sp1 => 'SP 1',
            self::Sp2 => 'SP 2',
            self::Sp3 => 'SP 3',
        };
    }

    /** 'teguran' (1–3) atau 'sp' (4–6). */
    public function jenis(): string
    {
        return $this->value <= 3 ? 'teguran' : 'sp';
    }

    /** Tingkat berikut pada eskalasi, null bila sudah mentok (SP3). */
    public function berikutnya(): ?self
    {
        return self::tryFrom($this->value + 1);
    }
}
