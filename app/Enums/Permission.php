<?php

namespace App\Enums;

enum Permission: string
{
    case LihatDataSendiri = 'lihat-data-sendiri';
    case AjukanCutiAbsen = 'ajukan-cuti-absen';
    case KelolaSdm = 'kelola-sdm';
    case AccCutiFinal = 'acc-cuti-final';
    case KerjakanTiketIt = 'kerjakan-tiket-it';
    case KerjakanTiketSarana = 'kerjakan-tiket-sarana';
    case KerjakanTiketAlkes = 'kerjakan-tiket-alkes';
    case LihatLaporan = 'lihat-laporan';
    case KelolaRbac = 'kelola-rbac';
    case PengaturanSistem = 'pengaturan-sistem';
}
