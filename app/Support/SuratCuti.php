<?php

namespace App\Support;

use App\Models\PengajuanCuti;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratCuti
{
    public static function generate(PengajuanCuti $pengajuan): string
    {
        $pengajuan->loadMissing(['karyawan.jabatan', 'karyawan.orgUnit', 'jenisCuti', 'approval.approver.jabatan']);

        $pdf = Pdf::loadView('surat.cuti', [
            'pengajuan' => $pengajuan,
            'ttd' => self::penandatangan($pengajuan),
        ])->setPaper('a4', 'portrait')->output();

        $path = 'cuti/'.$pengajuan->id.'/surat-'.Str::random(6).'.pdf';
        Storage::disk('local')->put($path, $pdf);

        return $path;
    }

    public static function penandatangan(PengajuanCuti $pengajuan): array
    {
        $pengajuan->loadMissing(['karyawan.jabatan', 'approval.approver.jabatan']);

        $entri = fn ($k, string $peran, $tgl, string $sumber): array => [
            'nama' => $k->nama_lengkap,
            'jabatan' => $k->jabatan?->nama,
            'peran' => $peran,
            'tanggal' => $tgl,
            'sumber' => $sumber,
            'qr' => TandaTanganQR::qr(TandaTanganQR::urlCuti($pengajuan, $sumber)),
        ];

        $signers = [];
        $signers[] = $entri($pengajuan->karyawan, 'Pemohon', $pengajuan->created_at, 'pemohon');

        foreach ($pengajuan->approval as $a) {
            $signers[] = $entri($a->approver, self::labelPeran($a->peran->value), $a->acted_at, $a->peran->value);
        }

        return $signers;
    }

    private static function labelPeran(string $v): string
    {
        return match ($v) {
            'koordinator' => 'Koordinator',
            'kabid' => 'Kabid',
            'hrd' => 'HRD',
            'direktur' => 'Direktur',
            default => ucfirst($v),
        };
    }
}
