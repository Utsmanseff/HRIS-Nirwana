<?php

namespace App\Support;

use App\Enums\ModeTemplate;
use App\Models\Jadwal;
use App\Models\OrgUnit;
use App\Models\TemplateJadwal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generasi jadwal bulanan dari template pola (deterministik).
 * - Mode rotasi:   posisi = ((tanggal − jangkar) mod panjang_siklus). Rotasi kontinu, abai nama hari.
 * - Mode mingguan: posisi = hari (Senin=0…Minggu=6). Jadwal jam-tetap per nama hari.
 * shift = siklus[posisi] (null = libur).
 *
 * $timpa=true  (manual): timpa penuh bulan sasaran (libur → hapus baris).
 * $timpa=false (auto):   NON-DESTRUKTIF — hanya isi tanggal yang belum ada; libur/edit manual tak disentuh.
 */
class TerapkanPola
{
    /** @return int jumlah baris jadwal yang di-set (shift, bukan libur) */
    public static function generate(OrgUnit $unit, int $tahun, int $bulan, ?int $dibuatOleh = null, bool $timpa = true): int
    {
        $tpl = TemplateJadwal::where('org_unit_id', $unit->id)->with('baris')->first();
        if (! $tpl || $tpl->baris->isEmpty()) {
            return 0;
        }

        $mingguan = $tpl->mode === ModeTemplate::Mingguan;
        $jangkar = $tpl->tanggal_jangkar->copy()->startOfDay();
        $awal = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        // Siklus per karyawan: [posisi => shift_id|null], terurut.
        $siklus = $tpl->baris->groupBy('karyawan_id')->map(
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
                    $row = Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tgl->toDateString())->first();

                    if (! $timpa) {
                        // NON-DESTRUKTIF: jangan sentuh yang sudah ada; libur → biarkan kosong.
                        if ($row || $shiftId === null) {
                            continue;
                        }
                        Jadwal::create(['karyawan_id' => $karyawanId, 'tanggal' => $tgl->toDateString(), 'shift_id' => $shiftId, 'dibuat_oleh' => $dibuatOleh]);
                        $dibuat++;

                        continue;
                    }

                    // TIMPA PENUH.
                    if ($shiftId === null) {
                        $row?->delete();

                        continue;
                    }
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
