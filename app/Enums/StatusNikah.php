<?php

// app/Enums/StatusNikah.php

namespace App\Enums;

enum StatusNikah: string
{
    case Belum = 'belum';
    case Menikah = 'menikah';
    case Cerai = 'cerai';
}
