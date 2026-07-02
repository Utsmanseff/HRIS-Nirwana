<?php

// app/Enums/JenisKontrak.php

namespace App\Enums;

enum JenisKontrak: string
{
    case PercobaanUnpaid = 'percobaan_unpaid';
    case Percobaan = 'percobaan';
    case Pkwt = 'pkwt';
    case Tetap = 'tetap';

    public function label(): string
    {
        return match ($this) {
            self::PercobaanUnpaid => 'Percobaan unpaid',
            self::Percobaan => 'Percobaan',
            self::Pkwt => 'PKWT',
            self::Tetap => 'Tetap',
        };
    }

    public function berbatasWaktu(): bool
    {
        return $this !== self::Tetap;
    }

    public function thresholdHari(): ?int
    {
        return match ($this) {
            self::PercobaanUnpaid => 3, self::Percobaan,self::Pkwt => 30, self::Tetap => null,
        };
    }
}
