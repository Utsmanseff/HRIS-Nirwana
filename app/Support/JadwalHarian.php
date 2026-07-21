<?php

namespace App\Support;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aturan jadwal per hari. Satu karyawan bisa punya lebih dari satu shift dalam
 * sehari (dinas ganda) — semua logika "shift mana" tinggal di sini.
 */
class JadwalHarian
{
    /** Semua jadwal karyawan pada satu tanggal, urut jam mulai shift. */
    public static function untuk(Karyawan $karyawan, Carbon|string $tanggal): Collection
    {
        $tgl = $tanggal instanceof Carbon ? $tanggal->toDateString() : $tanggal;

        return Jadwal::where('karyawan_id', $karyawan->id)
            ->whereDate('tanggal', $tgl)
            ->with('shift')
            ->get()
            ->sortBy(fn (Jadwal $j) => $j->shift?->jam_mulai ?? '99:99:99')
            ->values();
    }

    /** Rentang menit shift relatif tanggal jadwal; lintas tengah malam → selesai + 1440. */
    public static function rentang(Shift $shift): array
    {
        $mulai = self::menit($shift->jam_mulai);
        $selesai = self::menit($shift->jam_selesai);
        if ($selesai <= $mulai) {
            $selesai += 1440;
        }

        return [$mulai, $selesai];
    }

    /** Jarak dua waktu dalam sehari (menit), memutar tengah malam. Maks 720. */
    public static function jarakMelingkar(int $a, int $b): int
    {
        $d = abs($a - $b) % 1440;

        return min($d, 1440 - $d);
    }

    /** 'HH:MM[:SS]' → menit sejak tengah malam. */
    private static function menit(string $jam): int
    {
        [$h, $m] = array_map('intval', array_slice(explode(':', $jam), 0, 2));

        return $h * 60 + $m;
    }
}
