<?php

namespace App\Enums;

enum KodeJenisCuti: string
{
    case CutiTahunan = 'cuti_tahunan';
    case IzinBiasa = 'izin_biasa';
    case CutiSakit = 'cuti_sakit';
    case CutiMelahirkan = 'cuti_melahirkan';
}
