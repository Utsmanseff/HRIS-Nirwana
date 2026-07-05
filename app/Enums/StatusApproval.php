<?php

namespace App\Enums;

enum StatusApproval: string
{
    case Menunggu = 'menunggu';
    case Setuju = 'setuju';
    case Tolak = 'tolak';
}
