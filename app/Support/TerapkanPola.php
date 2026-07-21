<?php

namespace App\Support;

use App\Enums\ModeTemplate;
use App\Models\Jadwal;
use App\Models\TemplateJadwal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generasi jadwal bulanan dari SATU pola (deterministik). Cakupannya hanya anggota pola itu.
 * - Mode rotasi:   posisi = ((tanggal − jangkar) mod panjang_siklus). Rotasi kontinu, abai nama hari.
 * - Mode mingguan: posisi = hari (Senin=0…Minggu=6). Jadwal jam-tetap per nama hari.
 * shift = siklus[posisi] (null = libur).
 *
 * $timpa=true  (manual): timpa penuh bulan sasaran — SEMUA baris hari itu dihapus dulu
 *                        (dinas ganda manual ikut hilang), libur → tanpa baris.
 * $timpa=false (auto):   NON-DESTRUKTIF — hanya isi tanggal yang belum ada; libur/edit manual tak disentuh.
 */
class TerapkanPola
{
    /** @return int jumlah baris jadwal yang di-set (shift, bukan libur) */
    public static function untukPola(TemplateJadwal $pola, int $tahun, int $bulan, ?int $dibuatOleh = null, bool $timpa = true): int
    {
        $pola->loadMissing('baris');
        if ($pola->baris->isEmpty()) {
            return 0;
        }

        $mingguan = $pola->mode === ModeTemplate::Mingguan;
        $jangkar = $pola->tanggal_jangkar->copy()->startOfDay();
        $awal = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        // Siklus per karyawan: [posisi => shift_id|null], terurut.
        $siklus = $pola->baris->groupBy('karyawan_id')->map(
            fn ($rows) => $rows->sortBy('posisi')->pluck('shift_id')->all()
        );

        $dibuat = 0;

        DB::transaction(function () use ($siklus, $mingguan, $jangkar, $awal, $akhir, $dibuatOleh, $timpa, &$dibuat) {
            foreach ($siklus as $karyawanId => $urutan) {
                $panjang = count($urutan);
                if ($panjang === 0) {
                    continue;
                }

                for ($tgl = $awal->copy(); $tgl->lte($akhir); $tgl->addDay()) {
                    if ($mingguan) {
                        $shiftId = $urutan[$tgl->dayOfWeekIso - 1] ?? null;   // Senin=0…Minggu=6
                    } else {
                        $offset = (int) $jangkar->diffInDays($tgl, false);
                        $shiftId = $urutan[(($offset % $panjang) + $panjang) % $panjang];
                    }

                    // whereDate agar cocok dgn kolom date yang tersimpan 'Y-m-d 00:00:00'.
                    // Satu hari bisa punya BANYAK baris (dinas ganda) → jangan pakai first().
                    $hariIni = fn () => Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tgl->toDateString());

                    if (! $timpa) {
                        // NON-DESTRUKTIF: hari yang sudah punya baris apa pun tak disentuh.
                        if ($shiftId === null || $hariIni()->exists()) {
                            continue;
                        }
                        Jadwal::create(['karyawan_id' => $karyawanId, 'tanggal' => $tgl->toDateString(), 'shift_id' => $shiftId, 'dibuat_oleh' => $dibuatOleh]);
                        $dibuat++;

                        continue;
                    }

                    // TIMPA PENUH: buang semua baris hari itu (dinas ganda manual ikut hilang), lalu tulis pola.
                    $hariIni()->delete();
                    if ($shiftId === null) {
                        continue;
                    }
                    Jadwal::create(['karyawan_id' => $karyawanId, 'tanggal' => $tgl->toDateString(), 'shift_id' => $shiftId, 'dibuat_oleh' => $dibuatOleh]);
                    $dibuat++;
                }
            }
        });

        return $dibuat;
    }
}
