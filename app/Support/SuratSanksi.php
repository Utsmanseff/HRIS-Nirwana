<?php

namespace App\Support;

use App\Models\SanksiDisiplin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratSanksi
{
    /** Generate surat sanksi PDF, simpan ke disk local privat, kembalikan path relatif. */
    public static function generate(SanksiDisiplin $sanksi): string
    {
        $sanksi->loadMissing(['karyawan.jabatan', 'karyawan.orgUnit', 'approval.approver.jabatan']);

        $pdf = Pdf::loadView('surat.sanksi', ['sanksi' => $sanksi])
            ->setPaper('a4', 'portrait')
            ->output();

        $path = "sanksi/{$sanksi->id}/surat-".Str::slug($sanksi->nomor_surat ?? 'sanksi').'-'.Str::random(6).'.pdf';
        Storage::disk('local')->put($path, $pdf);

        return $path;
    }
}
