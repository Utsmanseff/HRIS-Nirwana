<?php

namespace App\Support;

use App\Models\Absensi;
use App\Models\Jadwal;
use Illuminate\Support\Collection;

/**
 * Menautkan baris absensi ke penugasan pengganti. `absensi` tak menyimpan
 * jadwal_id, jadi tautannya dicari lewat kunci (karyawan, tanggal, shift) —
 * satu query batch untuk seluruh rentang laporan, dipetakan di memori.
 */
class LabelPengganti
{
    /**
     * @param  Collection<int,Absensi>  $absensi
     * @return array<int,string> absensi_id => label (dinas biasa tak masuk peta)
     */
    public static function petaAbsensi(Collection $absensi): array
    {
        if ($absensi->isEmpty()) {
            return [];
        }

        $tanggal = $absensi->map(fn (Absensi $a) => $a->tanggal_kerja->toDateString());

        $jadwal = Jadwal::query()
            ->whereNotNull('pengganti_id')
            ->whereIn('karyawan_id', $absensi->pluck('karyawan_id')->unique()->all())
            // whereDate, bukan whereBetween: kolom date tersimpan 'Y-m-d 00:00:00'
            // sehingga batas atas berupa 'Y-m-d' polos akan membuang hari itu sendiri.
            ->whereDate('tanggal', '>=', $tanggal->min())
            ->whereDate('tanggal', '<=', $tanggal->max())
            ->with('penugasan.karyawanDigantikan')
            ->get();

        $peta = [];
        foreach ($jadwal as $j) {
            $label = $j->penugasan?->label();
            if ($label) {
                $peta[self::kunci($j->karyawan_id, $j->tanggal->toDateString(), $j->shift_id)] = $label;
            }
        }

        $hasil = [];
        foreach ($absensi as $a) {
            $k = self::kunci($a->karyawan_id, $a->tanggal_kerja->toDateString(), $a->shift_id);
            if (isset($peta[$k])) {
                $hasil[$a->id] = $peta[$k];
            }
        }

        return $hasil;
    }

    private static function kunci(int $karyawanId, string $tanggal, ?int $shiftId): string
    {
        return $karyawanId.'|'.$tanggal.'|'.($shiftId ?? '-');
    }
}
