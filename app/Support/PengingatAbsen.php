<?php

// app/Support/PengingatAbsen.php

namespace App\Support;

use App\Enums\StatusKaryawan;
use App\Enums\StatusPengajuanCuti;
use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\PengajuanCuti;
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

        if ($jadwal->isEmpty()) {
            return collect();
        }

        $karyawanIds = $jadwal->pluck('karyawan_id')->unique()->all();

        // Satu query untuk semua; kunci menyertakan shift_id supaya dinas ganda tak saling
        // membungkam. whereDate lagi — tanggal_kerja kena bug penyimpanan yang sama.
        $sudahAbsen = Absensi::whereIn('karyawan_id', $karyawanIds)
            ->where(function ($q) use ($tanggal) {
                foreach ($tanggal as $t) {
                    $q->orWhereDate('tanggal_kerja', $t);
                }
            })
            ->get(['karyawan_id', 'tanggal_kerja', 'shift_id'])
            ->map(fn (Absensi $a) => $a->karyawan_id.'|'.$a->tanggal_kerja->toDateString().'|'.$a->shift_id)
            ->flip();

        // whereDate di KEDUA sisi — kolom tanggal ter-cast datetime dan tersimpan
        // 'Y-m-d 00:00:00'; whereBetween dengan batas 'Y-m-d' polos membuang hari terakhir.
        $cuti = PengajuanCuti::where('status', StatusPengajuanCuti::Disetujui)
            ->whereIn('karyawan_id', $karyawanIds)
            ->whereDate('tanggal_mulai', '<=', $tanggal[1])
            ->whereDate('tanggal_selesai', '>=', $tanggal[0])
            ->get(['karyawan_id', 'tanggal_mulai', 'tanggal_selesai']);

        return $jadwal->filter(function (Jadwal $j) use ($sekarang, $p, $sudahAbsen, $cuti) {
            $kar = $j->karyawan;
            if (! $kar || $kar->status !== StatusKaryawan::Aktif || ! $kar->user) {
                return false;
            }

            $kunci = $j->karyawan_id.'|'.$j->tanggal->toDateString().'|'.$j->shift_id;
            if ($sudahAbsen->has($kunci)) {
                return false;
            }

            $tgl = $j->tanggal;
            $sedangCuti = $cuti->contains(
                fn (PengajuanCuti $c) => $c->karyawan_id === $j->karyawan_id
                    && $c->tanggal_mulai->lte($tgl)
                    && $c->tanggal_selesai->gte($tgl),
            );
            if ($sedangCuti) {
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
