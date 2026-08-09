<?php

// app/Enums/KodeJenisIzin.php

namespace App\Enums;

enum KodeJenisIzin: string
{
    case Str = 'str';
    case Sip = 'sip';
    case Sik = 'sik';
    case Sertifikat = 'sertifikat';

    public function label(): string
    {
        return match ($this) {
            self::Str => 'STR',
            self::Sip => 'SIP',
            self::Sik => 'SIK',
            self::Sertifikat => 'Sertifikat Kompetensi',
        };
    }
}
