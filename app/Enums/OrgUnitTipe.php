<?php

// app/Enums/OrgUnitTipe.php

namespace App\Enums;

enum OrgUnitTipe: string
{
    case Direktur = 'direktur';
    case Bidang = 'bidang';
    case Bagian = 'bagian';
    case Unit = 'unit';

    public function label(): string
    {
        return match ($this) {
            self::Direktur => 'Direktur',
            self::Bidang => 'Bidang',
            self::Bagian => 'Bagian',
            self::Unit => 'Unit',
        };
    }

    /** Level jabatan pimpinan untuk tipe unit ini. */
    public function levelPimpinan(): JabatanLevel
    {
        return match ($this) {
            self::Direktur => JabatanLevel::Direktur,
            self::Bidang, self::Bagian => JabatanLevel::Kabid,
            self::Unit => JabatanLevel::Koordinator,
        };
    }
}
