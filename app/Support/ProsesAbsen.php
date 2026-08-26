<?php

namespace App\Support;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * State machine sesi absensi.
 * $data = ['jam'=>Carbon, 'foto_path'=>?string, 'lat'=>float, 'long'=>float,
 *          'akurasi'=>float, 'wajah_verif'=>bool, 'flag_lokasi'=>array].
 */
class ProsesAbsen
{
    /** Sesi terbuka (belum pulang) milik karyawan, atau null. */
    public static function sesiAktif(Karyawan $karyawan): ?Absensi
    {
        return Absensi::where('karyawan_id', $karyawan->id)
            ->whereNull('jam_pulang')
            ->latest('jam_masuk')
            ->first();
    }

    /**
     * Satu pintu untuk absen dari UI: ambil kunci per-karyawan, baru putuskan
     * masuk/pulang DI DALAM kunci.
     *
     * Tanpa ini, "cek sesi" dan "tulis baris" adalah dua langkah terpisah: dua
     * request paralel (dua tab, dua perangkat, atau satu tap ganda yang lolos
     * gerbang UI) sama-sama melihat "belum ada sesi" lalu sama-sama membuat sesi
     * masuk. Itulah absen dobel yang terlihat di lapangan; guard RuntimeException
     * di masuk()/pulang() saja tidak cukup karena ia juga check-then-act.
     *
     * masuk()/pulang() sengaja dibiarkan tanpa kunci — dipakai seeder & test yang
     * memang serial, dan menguncinya di sana bikin kunci bersarang.
     */
    public static function catat(Karyawan $karyawan, array $data): Absensi
    {
        try {
            return Cache::lock("absen-catat:{$karyawan->id}", 10)->block(5, function () use ($karyawan, $data) {
                return DB::transaction(fn () => self::sesiAktif($karyawan)
                    ? self::pulang($karyawan, $data)
                    : self::masuk($karyawan, $data));
            });
        } catch (LockTimeoutException) {
            throw new RuntimeException('Absen sebelumnya masih diproses — tunggu sebentar lalu coba lagi.');
        }
    }

    /**
     * Buka sesi baru (absen masuk). Snapshot shift dari jadwal hari itu (bila ada) + hitung telat.
     * Dinas ganda: shift dipilih JadwalHarian (terdekat & belum terpakai), bukan baris pertama.
     */
    public static function masuk(Karyawan $karyawan, array $data): Absensi
    {
        if (self::sesiAktif($karyawan)) {
            throw new RuntimeException('Masih ada sesi aktif — absen pulang dulu.');
        }

        $jam = $data['jam'];
        $jadwal = JadwalHarian::pilihUntukAbsen($karyawan, $jam);
        $shift = $jadwal?->shift;

        $telat = $shift
            ? EvaluasiAbsensi::telatMenit($jam, $shift->jam_mulai, $shift->toleransi_telat)
            : null;

        return Absensi::create([
            'karyawan_id' => $karyawan->id,
            'tanggal_kerja' => $jam->toDateString(),
            'shift_id' => $shift?->id,
            'shift_nama' => $shift?->nama,
            'shift_mulai' => $shift?->jam_mulai,
            'shift_selesai' => $shift?->jam_selesai,
            'shift_toleransi' => $shift?->toleransi_telat,
            'jam_masuk' => $jam,
            'foto_masuk_path' => $data['foto_path'] ?? null,
            'lat_masuk' => $data['lat'],
            'long_masuk' => $data['long'],
            'akurasi_masuk' => $data['akurasi'],
            'wajah_verif_masuk' => $data['wajah_verif'],
            'flag_lokasi_masuk' => $data['flag_lokasi'] ?: null,
            'telat_menit' => $telat,
        ]);
    }

    /** Tutup sesi aktif (absen pulang). Hitung pulang cepat bila ada snapshot shift. */
    public static function pulang(Karyawan $karyawan, array $data): Absensi
    {
        $sesi = self::sesiAktif($karyawan);
        if (! $sesi) {
            throw new RuntimeException('Tidak ada sesi aktif — absen masuk dulu.');
        }

        $jam = $data['jam'];
        $pulangCepat = $sesi->adaShift()
            ? EvaluasiAbsensi::pulangCepatMenit($sesi->jam_masuk, $jam, $sesi->shift_mulai, $sesi->shift_selesai)
            : null;

        $sesi->update([
            'jam_pulang' => $jam,
            'foto_pulang_path' => $data['foto_path'] ?? null,
            'lat_pulang' => $data['lat'],
            'long_pulang' => $data['long'],
            'akurasi_pulang' => $data['akurasi'],
            'wajah_verif_pulang' => $data['wajah_verif'],
            'flag_lokasi_pulang' => $data['flag_lokasi'] ?: null,
            'pulang_cepat_menit' => $pulangCepat,
        ]);

        return $sesi->refresh();
    }
}
