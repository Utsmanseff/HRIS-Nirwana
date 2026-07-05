<?php

namespace App\Enums;

enum StatusPengajuanCuti: string
{
    case Diajukan = 'diajukan';
    case Diproses = 'diproses';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    /** Status yang menahan saldo (belum dipotong, tapi mengurangi efektif). */
    public function pending(): bool
    {
        return $this === self::Diajukan || $this === self::Diproses;
    }
}
