<?php

namespace App\Support;

use App\Models\Jadwal;
use App\Models\OrgUnit;
use App\Models\TemplateJadwal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generasi jadwal bulanan dari template pola (deterministik).
 * posisi = ((tanggal − jangkar) mod panjang_siklus), shift = siklus[posisi] (null = libur).
 * Menimpa jadwal bulan sasaran untuk karyawan bersiklus (libur → hapus baris).
 */
class TerapkanPola
{
    /** @return int jumlah baris jadwal yang di-set (shift, bukan libur) */
    public static function generate(OrgUnit $unit, int $tahun, int $bulan, ?int $dibuatOleh = null): int
    {
        $tpl = TemplateJadwal::where('org_unit_id', $unit->id)->with('baris')->first();
        if (! $tpl || $tpl->baris->isEmpty()) {
            return 0;
        }

        $jangkar = $tpl->tanggal_jangkar->copy()->startOfDay();
        $awal = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        // Siklus per karyawan: [posisi => shift_id|null], terurut.
        $siklus = $tpl->baris->groupBy('karyawan_id')->map(
            fn ($rows) => $rows->sortBy('posisi')->pluck('shift_id')->all()
        );

        $dibuat = 0;

        DB::transaction(function () use ($siklus, $jangkar, $awal, $akhir, $dibuatOleh, &$dibuat) {
            foreach ($siklus as $karyawanId => $urutan) {
                $panjang = count($urutan);
                if ($panjang === 0) {
                    continue;
                }

                for ($tgl = $awal->copy(); $tgl->lte($akhir); $tgl->addDay()) {
                    $offset = (int) $jangkar->diffInDays($tgl, false);
                    $posisi = (($offset % $panjang) + $panjang) % $panjang;
                    $shiftId = $urutan[$posisi];

                    if ($shiftId === null) {
                        // Libur → hapus jadwal lama bila ada.
                        Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tgl->toDateString())->delete();

                        continue;
                    }

                    // whereDate agar cocok dgn kolom date yang tersimpan 'Y-m-d 00:00:00'.
                    $row = Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tgl->toDateString())->first();
                    if ($row) {
                        $row->update(['shift_id' => $shiftId, 'dibuat_oleh' => $dibuatOleh]);
                    } else {
                        Jadwal::create(['karyawan_id' => $karyawanId, 'tanggal' => $tgl->toDateString(), 'shift_id' => $shiftId, 'dibuat_oleh' => $dibuatOleh]);
                    }
                    $dibuat++;
                }
            }
        });

        return $dibuat;
    }
}
