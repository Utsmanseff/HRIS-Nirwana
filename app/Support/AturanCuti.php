<?php

// app/Support/AturanCuti.php

namespace App\Support;

use App\Enums\KodeJenisCuti;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AturanCuti
{
    public const MAKS_CUTI_TAHUNAN = 6;

    /** Jenis cuti yang boleh dipilih pemohon (sesuai eligibility saldo). */
    public static function jenisTersedia(Karyawan $karyawan): Collection
    {
        $aktif = JenisCuti::aktif()->orderBy('id')->get();

        if (SaldoCuti::untuk($karyawan)->eligible()) {
            return $aktif;
        }

        // Belum eligible (tahun pertama): blokir cuti_tahunan saja.
        // Sakit/melahirkan/izin tetap boleh (keputusan user 2026-07-05, override teks spec §2/§A1).
        return $aktif->where('kode', '!==', KodeJenisCuti::CutiTahunan)->values();
    }

    /**
     * Kembalikan map error [field => pesan]. Kosong = valid.
     *
     * @return array<string, string>
     */
    public static function periksa(
        Karyawan $karyawan,
        JenisCuti $jenis,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $jumlahHari,
        bool $adaLampiran,
    ): array {
        $err = [];
        $mulai = Carbon::parse($tanggalMulai)->startOfDay();
        $selesai = Carbon::parse($tanggalSelesai)->startOfDay();

        if ($selesai->lessThan($mulai)) {
            $err['tanggalSelesai'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        }

        $rentangHari = $selesai->greaterThanOrEqualTo($mulai) ? $mulai->diffInDays($selesai) + 1 : 0;
        if ($jumlahHari < 1) {
            $err['jumlahHari'] = 'Jumlah hari minimal 1.';
        } elseif ($rentangHari > 0 && $jumlahHari > $rentangHari) {
            $err['jumlahHari'] = "Jumlah hari melebihi rentang tanggal ({$rentangHari} hari).";
        }

        if ($jenis->kode === KodeJenisCuti::CutiTahunan && ! isset($err['jumlahHari'])) {
            if ($jumlahHari > self::MAKS_CUTI_TAHUNAN) {
                $err['jumlahHari'] = 'Cuti tahunan maksimal '.self::MAKS_CUTI_TAHUNAN.' hari per pengajuan.';
            } else {
                $efektif = SaldoCuti::untuk($karyawan)->efektif($mulai);
                if ($jumlahHari > $efektif) {
                    $err['jumlahHari'] = "Melebihi saldo cuti tahunan (sisa {$efektif} hari).";
                }
            }
        }

        if (! $jenis->boleh_backdate && $mulai->lessThan(Carbon::today())) {
            $err['tanggalMulai'] = 'Tanggal mulai tidak boleh di masa lampau untuk jenis ini.';
        }

        if ($jenis->butuh_lampiran && ! $adaLampiran) {
            $err['lampiran'] = 'Lampiran wajib untuk jenis cuti ini.';
        }

        return $err;
    }
}
