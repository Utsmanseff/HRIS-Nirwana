<?php

// app/Enums/StatusKaryawan.php

namespace App\Enums;

enum StatusKaryawan: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
}
