<?php

namespace App\Support;

use App\Enums\StatusPengajuanCuti;
use App\Enums\StatusPengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenggantiCuti;
use App\Models\User;
use App\Notifications\DitunjukJadiPengganti;
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

            $baris = PenggantiCuti::create([
                'pengajuan_cuti_id' => $cuti->id,
                'karyawan_id' => $pengganti->id,
                'tanggal_mulai' => Carbon::parse($cuti->tanggal_mulai)->toDateString(),
                'tanggal_selesai' => Carbon::parse($cuti->tanggal_selesai)->toDateString(),
                'status' => StatusPengganti::Aktif,
                'dibuat_oleh' => $oleh->id,
            ]);

            // Cuti yang sudah disetujui → salinan jadwal langsung berlaku.
            self::generateSaatDisetujui($cuti->fresh());

            return $baris;
        });
    }

    /**
     * Materialisasi rencana aktif jadi baris jadwal untuk pengganti.
     * No-op bila cuti belum Disetujui. Idempoten. Mengembalikan jumlah baris baru.
     */
    public static function generateSaatDisetujui(PengajuanCuti $cuti): int
    {
        if ($cuti->status !== StatusPengajuanCuti::Disetujui) {
            return 0;
        }

        $total = 0;

        foreach ($cuti->pengganti()->aktif()->with('karyawan')->get() as $rencana) {
            $dibuat = 0;
            $mulai = Carbon::parse($rencana->tanggal_mulai);
            $selesai = Carbon::parse($rencana->tanggal_selesai);

            for ($t = $mulai->copy()->startOfDay(); $t->lte($selesai); $t->addDay()) {
                $shiftPemohon = JadwalHarian::untuk($cuti->karyawan, $t)
                    ->filter(fn (Jadwal $j) => $j->shift !== null && $j->pengganti_cuti_id === null);

                foreach ($shiftPemohon as $j) {
                    // Lewati bila pengganti sudah punya baris shift itu (idempoten +
                    // menjaga unique (karyawan, tanggal, shift)).
                    $ada = Jadwal::where('karyawan_id', $rencana->karyawan_id)
                        ->whereDate('tanggal', $t->toDateString())
                        ->where('shift_id', $j->shift_id)
                        ->exists();
                    if ($ada) {
                        continue;
                    }

                    Jadwal::create([
                        'karyawan_id' => $rencana->karyawan_id,
                        'tanggal' => $t->toDateString(),
                        'shift_id' => $j->shift_id,
                        'dibuat_oleh' => $rencana->dibuat_oleh,
                        'pengganti_cuti_id' => $rencana->id,
                    ]);
                    $dibuat++;
                }
            }

            if ($dibuat > 0) {
                $rencana->karyawan->user?->notify(new DitunjukJadiPengganti($cuti, $rencana));
            }
            $total += $dibuat;
        }

        return $total;
    }

    /** Estafet Jalur-1: mulai tanggal X, cakupan berpindah ke $penggantiBaru sampai akhir cuti. */
    public static function alihkan(PengajuanCuti $cuti, Carbon $mulaiBaru, Karyawan $penggantiBaru, User $oleh): void
    {
        self::pastikanBisaEstafet($cuti, $mulaiBaru);

        if ($penggantiBaru->id === $cuti->karyawan_id) {
            throw new ProsesPenggantiException('Pemohon tak bisa menjadi pengganti dirinya sendiri.');
        }

        $akhir = Carbon::parse($cuti->tanggal_selesai);
        $bentrok = self::cekBentrok($penggantiBaru, $cuti, $mulaiBaru, $akhir);
        if ($bentrok) {
            throw new ProsesPenggantiException(self::pesanBentrok($bentrok));
        }

        DB::transaction(function () use ($cuti, $mulaiBaru, $penggantiBaru, $oleh, $akhir) {
            $beririsan = $cuti->pengganti()->aktif()->get()
                ->filter(fn (PenggantiCuti $p) => Carbon::parse($p->tanggal_selesai)->gte($mulaiBaru));

            foreach ($beririsan as $p) {
                if (Carbon::parse($p->tanggal_mulai)->lt($mulaiBaru)) {
                    self::hapusJadwalRencana([$p->id], $mulaiBaru);
                    $p->update(['tanggal_selesai' => $mulaiBaru->copy()->subDay()->toDateString()]);
                } else {
                    self::hapusJadwalRencana([$p->id]);
                    $p->delete();
                }
            }

            PenggantiCuti::create([
                'pengajuan_cuti_id' => $cuti->id,
                'karyawan_id' => $penggantiBaru->id,
                'tanggal_mulai' => $mulaiBaru->toDateString(),
                'tanggal_selesai' => $akhir->toDateString(),
                'status' => StatusPengganti::Aktif,
                'dibuat_oleh' => $oleh->id,
            ]);

            self::generateSaatDisetujui($cuti->fresh());
        });
    }

    /** Estafet hanya untuk cuti disetujui, dan tanggal harus di dalam masa cuti. */
    private static function pastikanBisaEstafet(PengajuanCuti $cuti, Carbon $mulai): void
    {
        if ($cuti->status !== StatusPengajuanCuti::Disetujui) {
            throw new ProsesPenggantiException('Estafet hanya untuk cuti yang sudah disetujui.');
        }
        if ($mulai->lt(Carbon::parse($cuti->tanggal_mulai)) || $mulai->gt(Carbon::parse($cuti->tanggal_selesai))) {
            throw new ProsesPenggantiException('Tanggal mulai estafet di luar masa cuti.');
        }
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
