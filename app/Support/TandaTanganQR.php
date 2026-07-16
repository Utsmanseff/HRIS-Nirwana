<?php

namespace App\Support;

use App\Models\SanksiDisiplin;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\URL;

/** Bikin URL verifikasi bertanda-tangan (signed) + render QR PNG data-URI untuk surat sanksi. */
class TandaTanganQR
{
    /** URL publik bertanda-tangan untuk satu penandatangan surat. $sumber ∈ penerbit|pengusul|kabid. */
    public static function url(SanksiDisiplin $sanksi, string $sumber): string
    {
        return URL::signedRoute('verifikasi.sanksi', [
            'sanksi' => $sanksi->id,
            'sumber' => $sumber,
        ]);
    }

    /** Render URL jadi QR PNG data-URI (untuk embed <img> di PDF dompdf). */
    public static function qr(string $url, int $ukuran = 300): string
    {
        $qr = new QrCode(data: $url, size: $ukuran, margin: 8);

        return (new PngWriter())->write($qr)->getDataUri();
    }
}
