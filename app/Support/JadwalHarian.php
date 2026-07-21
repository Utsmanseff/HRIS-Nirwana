<?php

namespace App\Support;

use App\Models\Absensi;
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

    /**
     * Jadwal yang dipakai saat absen masuk: shift terdekat (jarak melingkar) yang
     * BELUM punya sesi absensi pada tanggal itu. Null → mode catat (tanpa evaluasi).
     */
    public static function pilihUntukAbsen(Karyawan $karyawan, Carbon $jam): ?Jadwal
    {
        $kandidat = self::untuk($karyawan, $jam)->filter(fn (Jadwal $j) => $j->shift !== null);
        if ($kandidat->isEmpty()) {
            return null;
        }

        $terpakai = Absensi::where('karyawan_id', $karyawan->id)
            ->whereDate('tanggal_kerja', $jam->toDateString())
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->all();

        $sisa = $kandidat->reject(fn (Jadwal $j) => in_array($j->shift_id, $terpakai));
        if ($sisa->isEmpty()) {
            return null;
        }

        $menitAbsen = self::menit($jam->format('H:i'));

        return $sisa->sort(function (Jadwal $a, Jadwal $b) use ($menitAbsen) {
            $ja = self::jarakMelingkar($menitAbsen, self::menit($a->shift->jam_mulai));
            $jb = self::jarakMelingkar($menitAbsen, self::menit($b->shift->jam_mulai));

            return $ja <=> $jb ?: strcmp($a->shift->jam_mulai, $b->shift->jam_mulai);
        })->first();
    }

    /**
     * Benar bila jam $shift beririsan dengan jadwal lain karyawan di tanggal itu.
     * Sentuhan ujung (16:00-00:00 lalu 00:00-08:00) BUKAN bentrok.
     * Batas sadar: hanya dihitung dalam satu tanggal jadwal.
     */
    public static function bentrok(Karyawan $karyawan, Carbon|string $tanggal, Shift $shift, ?int $abaikanJadwalId = null): bool
    {
        [$mulaiBaru, $selesaiBaru] = self::rentang($shift);

        foreach (self::untuk($karyawan, $tanggal) as $j) {
            if ($j->id === $abaikanJadwalId || $j->shift === null) {
                continue;
            }
            [$mulai, $selesai] = self::rentang($j->shift);
            if ($mulaiBaru < $selesai && $mulai < $selesaiBaru) {
                return true;
            }
        }

        return false;
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
