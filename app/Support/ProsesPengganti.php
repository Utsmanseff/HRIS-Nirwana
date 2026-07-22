<?php

namespace App\Support;

use App\Enums\StatusPengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Satu rumah aturan pengganti cuti: siapa boleh menutup shift siapa, kapan
 * salinan jadwal dibuat, dan bagaimana estafet memotong rentang.
 */
class ProsesPengganti
{
    /**
     * Bentrok jam antara jadwal $pengganti dan shift $pemohon pada tiap hari [$mulai,$selesai].
     * Kosong = aman. Baris salinan milik rencana yang sedang diganti bisa diabaikan.
     *
     * @param  list<int>  $abaikanRencanaIds
     * @return list<array{tanggal:string, shift_pemohon:string, shift_pengganti:string}>
     */
    public static function cekBentrokRentang(
        Karyawan $pengganti,
        Karyawan $pemohon,
        Carbon $mulai,
        Carbon $selesai,
        array $abaikanRencanaIds = [],
    ): array {
        $bentrok = [];

        for ($t = $mulai->copy()->startOfDay(); $t->lte($selesai); $t->addDay()) {
            // Shift asli pemohon (salinan pengganti milik orang lain tak ikut ditutup).
            $shiftPemohon = JadwalHarian::untuk($pemohon, $t)
                ->filter(fn (Jadwal $j) => $j->shift !== null && $j->pengganti_cuti_id === null);
            if ($shiftPemohon->isEmpty()) {
                continue; // pemohon libur hari itu
            }

            $milikPengganti = JadwalHarian::untuk($pengganti, $t)
                ->filter(fn (Jadwal $j) => $j->shift !== null
                    && ! in_array($j->pengganti_cuti_id, $abaikanRencanaIds, true));

            foreach ($shiftPemohon as $sp) {
                [$m1, $s1] = JadwalHarian::rentang($sp->shift);
                foreach ($milikPengganti as $mp) {
                    [$m2, $s2] = JadwalHarian::rentang($mp->shift);
                    if ($m1 < $s2 && $m2 < $s1) {
                        $bentrok[] = [
                            'tanggal' => $t->toDateString(),
                            'shift_pemohon' => $sp->shift->kode,
                            'shift_pengganti' => $mp->shift->kode,
                        ];
                    }
                }
            }
        }

        return $bentrok;
    }

    /** Versi berbasis pengajuan; default rentang = seluruh masa cuti. */
    public static function cekBentrok(
        Karyawan $pengganti,
        PengajuanCuti $cuti,
        ?Carbon $mulai = null,
        ?Carbon $selesai = null,
    ): array {
        return self::cekBentrokRentang(
            $pengganti,
            $cuti->karyawan,
            $mulai ?? Carbon::parse($cuti->tanggal_mulai),
            $selesai ?? Carbon::parse($cuti->tanggal_selesai),
            $cuti->pengganti()->pluck('id')->all(),
        );
    }

    /** Pesan siap-tampil dari hasil cekBentrok (tanggal + shift penyebab). */
    public static function pesanBentrok(array $bentrok): string
    {
        $b = $bentrok[0];

        return "Jadwal bentrok pada {$b['tanggal']}: shift {$b['shift_pengganti']} pengganti"
            ." beririsan dengan shift {$b['shift_pemohon']} pemohon.";
    }

    /** Tetapkan pengganti untuk seluruh rentang cuti; ganti baris aktif yang ada. */
    public static function tetapkan(PengajuanCuti $cuti, Karyawan $pengganti, User $oleh): PenggantiCuti
    {
        if ($pengganti->id === $cuti->karyawan_id) {
            throw new ProsesPenggantiException('Pemohon tak bisa menjadi pengganti dirinya sendiri.');
        }

        $bentrok = self::cekBentrok($pengganti, $cuti);
        if ($bentrok) {
            throw new ProsesPenggantiException(self::pesanBentrok($bentrok));
        }

        return DB::transaction(function () use ($cuti, $pengganti, $oleh) {
            $lama = $cuti->pengganti()->aktif()->pluck('id')->all();
            self::hapusJadwalRencana($lama);
            PenggantiCuti::whereIn('id', $lama)->delete();

            return PenggantiCuti::create([
                'pengajuan_cuti_id' => $cuti->id,
                'karyawan_id' => $pengganti->id,
                'tanggal_mulai' => Carbon::parse($cuti->tanggal_mulai)->toDateString(),
                'tanggal_selesai' => Carbon::parse($cuti->tanggal_selesai)->toDateString(),
                'status' => StatusPengganti::Aktif,
                'dibuat_oleh' => $oleh->id,
            ]);
        });
    }

    /** Hapus baris jadwal hasil salinan milik rencana tertentu (opsional: hanya sejak tanggal). */
    private static function hapusJadwalRencana(array $ids, ?Carbon $sejak = null): void
    {
        if (! $ids) {
            return;
        }

        Jadwal::whereIn('pengganti_cuti_id', $ids)
            ->when($sejak, fn ($q) => $q->whereDate('tanggal', '>=', $sejak->toDateString()))
            ->delete();
    }
}
