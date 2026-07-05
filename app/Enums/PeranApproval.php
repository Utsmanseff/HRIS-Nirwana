<?php

namespace App\Enums;

enum PeranApproval: string
{
    case Koordinator = 'koordinator';
    case Kabid = 'kabid';
    case Hrd = 'hrd';
    case Direktur = 'direktur';
}
