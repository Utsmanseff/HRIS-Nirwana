<?php

namespace App\Enums;

enum Role: string
{
    case Karyawan = 'Karyawan';
    case StaffHr = 'Staff HR';
    case Hrd = 'HRD';
    case It = 'IT';
    case Teknisi = 'Teknisi';
    case Atem = 'ATEM';
    case Direktur = 'Direktur';
    case AdminSistem = 'Admin Sistem';
}
