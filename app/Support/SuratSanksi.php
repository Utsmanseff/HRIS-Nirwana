<?php

namespace App\Support;

use App\Enums\JabatanLevel;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratSanksi
{
    /** Generate surat sanksi PDF, simpan ke disk local privat, kembalikan path relatif. */
    public static function generate(SanksiDisiplin $sanksi): string
    {
        $sanksi->loadMissing(['karyawan.jabatan', 'karyawan.orgUnit', 'pengusul.jabatan', 'approval.approver.jabatan']);

        $pdf = Pdf::loadView('surat.sanksi', [
            'sanksi' => $sanksi,
            'penandatangan' => self::penandatangan($sanksi),
        ])->setPaper('a4', 'portrait')->output();

        $path = "sanksi/{$sanksi->id}/surat-".Str::slug($sanksi->nomor_surat ?? 'sanksi').'-'.Str::random(6).'.pdf';
        Storage::disk('local')->put($path, $pdf);

        return $path;
    }

    /**
     * Daftar penanda tangan surat = pengusul + rantai approver (dedup).
     * Direktur pengusul → tanda tangan tunggal (otoritas tertinggi). HRD pengusul == approver → satu ttd.
     *
     * @return list<array{nama:string, jabatan:?string, peran:string, tanggal:?\Illuminate\Support\Carbon}>
     */
    public static function penandatangan(SanksiDisiplin $sanksi): array
    {
        $sanksi->loadMissing(['pengusul.jabatan', 'approval.approver.jabatan']);

        $entri = fn (Karyawan $k, string $peran, $tgl): array => [
            'nama' => $k->nama_lengkap,
            'jabatan' => $k->jabatan?->nama,
            'peran' => $peran,
            'tanggal' => $tgl,
        ];

        // Direktur pengusul → hanya Direktur yang menandatangani.
        if (($sanksi->pengusul->jabatan?->level?->value ?? 0) === JabatanLevel::Direktur->value) {
            return [$entri($sanksi->pengusul, 'Direktur', $sanksi->created_at)];
        }

        $approverIds = $sanksi->approval->pluck('approver_id');
        $list = [];

        // Pengusul ikut tanda tangan (kecuali sudah muncul sebagai approver, mis. HRD usul-langsung).
        if (! $approverIds->contains($sanksi->pengusul_id)) {
            $list[] = $entri($sanksi->pengusul, 'Pengusul', $sanksi->created_at);
        }

        foreach ($sanksi->approval as $a) {
            $list[] = $entri($a->approver, self::labelPeran($a->peran->value), $a->acted_at);
        }

        return $list;
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
