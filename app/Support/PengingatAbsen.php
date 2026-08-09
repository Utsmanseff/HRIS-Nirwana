<?php

// app/Support/PengingatAbsen.php

namespace App\Support;

use App\Enums\StatusKaryawan;
use App\Models\Jadwal;
use App\Models\PengaturanAbsensi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aturan pengingat absen — turunan murni, tanpa efek samping.
 * Waktu selalu bisa disuntik supaya seluruh logika jendela teruji dengan Carbon dibekukan.
 */
class PengingatAbsen
{
    /**
     * Baris jadwal yang jam masuknya sudah terlewat tapi belum diabsen.
     *
     * @return Collection<int, Jadwal>
     */
    public static function masukTerlewat(?Carbon $sekarang = null): Collection
    {
        $sekarang = $sekarang ? $sekarang->copy() : now();
        $p = PengaturanAbsensi::ambil();

        $tanggal = [
            $sekarang->copy()->subDay()->toDateString(),
            $sekarang->toDateString(),
        ];

        // Kemarin ikut diambil karena shift malam masih dalam jendela sampai pagi berikutnya.
        // jadwal.shift_id selalu NOT NULL (beda dari pola_jadwal, tempat null = libur) —
        // tak perlu whereNotNull di sini.
        // whereDate (bukan whereIn polos): kolom tanggal ter-cast 'date' tapi tersimpan
        // 'Y-m-d 00:00:00' — whereIn membandingkan string apa adanya dan tak pernah cocok.
        $jadwal = Jadwal::with(['shift', 'karyawan.user'])
            ->where(function ($q) use ($tanggal) {
                foreach ($tanggal as $t) {
                    $q->orWhereDate('tanggal', $t);
                }
            })
            ->get();

        return $jadwal->filter(function (Jadwal $j) use ($sekarang, $p) {
            $kar = $j->karyawan;
            if (! $kar || $kar->status !== StatusKaryawan::Aktif || ! $kar->user) {
                return false;
            }

            return self::dalamJendelaMasuk($j, $sekarang, $p);
        })->values();
    }

    private static function dalamJendelaMasuk(Jadwal $j, Carbon $sekarang, PengaturanAbsensi $p): bool
    {
        [$mulaiMenit, $selesaiMenit] = JadwalHarian::rentang($j->shift);

        $awalHari = $j->tanggal->copy()->startOfDay();
        $mulai = $awalHari->copy()->addMinutes($mulaiMenit + $j->shift->toleransi_telat + $p->jeda_masuk_menit);
        $selesai = $awalHari->copy()->addMinutes($selesaiMenit);

        return $sekarang->gte($mulai) && $sekarang->lte($selesai);
    }
}
