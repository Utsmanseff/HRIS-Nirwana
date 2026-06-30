<?php

// app/Enums/AlasanNonaktif.php

namespace App\Enums;

enum AlasanNonaktif: string
{
    case Resign = 'resign';
    case KontrakBerakhir = 'kontrak_berakhir';
    case Phk = 'phk';
    case Pensiun = 'pensiun';
    case Meninggal = 'meninggal';
}
