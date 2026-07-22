<?php

namespace App\Support;

use App\Enums\StatusKaryawan;
use App\Enums\StatusPengajuanCuti;
use App\Enums\StatusPengganti;
use App\Enums\TipePengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use App\Models\User;
use App\Notifications\DitunjukJadiPengganti;
use App\Notifications\UsulanPenggantiMasuk;
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
                ->filter(fn (Jadwal $j) => $j->shift !== null && $j->pengganti_id === null);
            if ($shiftPemohon->isEmpty()) {
                continue; // pemohon libur hari itu
            }

            $milikPengganti = JadwalHarian::untuk($pengganti, $t)
                ->filter(fn (Jadwal $j) => $j->shift !== null
                    && ! in_array($j->pengganti_id, $abaikanRencanaIds, true));

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

    /** Versi berbasis kasus; default rentang = seluruh rentang kasus. */
    public static function cekBentrok(
        Karyawan $pengganti,
        PengajuanCuti|Karyawan $kasus,
        ?Carbon $mulai = null,
        ?Carbon $selesai = null,
    ): array {
        $k = KasusPengganti::dari($kasus);
        $akhir = $selesai ?? $k->batasAkhir();
        if (! $akhir) {
            return [];   // lowongan tanpa jejak jadwal → tak ada yang bisa bentrok
        }

        return self::cekBentrokRentang(
            $pengganti,
            $k->digantikan,
            $mulai ?? $k->mulai,
            $akhir,
            $k->rencana()->pluck('id')->all(),
        );
    }

    /** Pesan siap-tampil dari hasil cekBentrok (tanggal + shift penyebab). */
    public static function pesanBentrok(array $bentrok): string
    {
        $b = $bentrok[0];

        return "Jadwal bentrok pada {$b['tanggal']}: shift {$b['shift_pengganti']} pengganti"
            ." beririsan dengan shift {$b['shift_pemohon']} pemohon.";
    }

    /** Tetapkan pengganti untuk seluruh rentang kasus; ganti baris aktif yang ada. */
    public static function tetapkan(PengajuanCuti|Karyawan $kasus, Karyawan $pengganti, User $oleh): PenugasanPengganti
    {
        $k = KasusPengganti::dari($kasus);

        if ($k->tipe === TipePengganti::Lowongan && $k->digantikan->status !== StatusKaryawan::Nonaktif) {
            throw new ProsesPenggantiException('Lowongan hanya untuk karyawan nonaktif.');
        }
        if ($pengganti->id === $k->digantikan->id) {
            throw new ProsesPenggantiException('Pemohon tak bisa menjadi pengganti dirinya sendiri.');
        }
        if ($pengganti->status !== StatusKaryawan::Aktif) {
            throw new ProsesPenggantiException('Karyawan nonaktif tak bisa menjadi pengganti.');
        }

        $bentrok = self::cekBentrok($pengganti, $kasus);
        if ($bentrok) {
            throw new ProsesPenggantiException(self::pesanBentrok($bentrok));
        }

        return DB::transaction(function () use ($k, $pengganti, $oleh) {
            $lama = $k->rencana()->aktif()->pluck('id')->all();
            self::hapusJadwalRencana($lama);
            PenugasanPengganti::whereIn('id', $lama)->delete();

            $baris = PenugasanPengganti::create($k->atribut() + [
                'karyawan_id' => $pengganti->id,
                'tanggal_mulai' => $k->mulai->toDateString(),
                'tanggal_selesai' => $k->akhir?->toDateString(),
                'status' => StatusPengganti::Aktif,
                'dibuat_oleh' => $oleh->id,
            ]);

            // Cuti disetujui / lowongan → salinan jadwal langsung berlaku.
            self::sinkronSalinan($baris->fresh());

            return $baris;
        });
    }

    /**
     * Materialisasi SATU baris rencana jadi baris jadwal untuk penggantinya.
     * Cuti: no-op bila pengajuannya belum Disetujui. Lowongan: selalu jalan.
     * Idempoten — baris (karyawan, tanggal, shift) yang sudah ada dilewati.
     */
    public static function sinkronSalinan(PenugasanPengganti $rencana): int
    {
        if ($rencana->status !== StatusPengganti::Aktif) {
            return 0;
        }
        if ($rencana->tipe === TipePengganti::Cuti
            && $rencana->pengajuan?->status !== StatusPengajuanCuti::Disetujui) {
            return 0;
        }

        $digantikan = $rencana->karyawanDigantikan;
        if (! $digantikan) {
            return 0;
        }

        $mulai = Carbon::parse($rencana->tanggal_mulai)->startOfDay();
        $selesai = $rencana->tanggal_selesai
            ? Carbon::parse($rencana->tanggal_selesai)->startOfDay()
            : KasusPengganti::dari($digantikan)->batasAkhir();

        if (! $selesai) {
            return 0;
        }

        $dibuat = 0;

        for ($t = $mulai->copy(); $t->lte($selesai); $t->addDay()) {
            $shiftAsli = JadwalHarian::untuk($digantikan, $t)
                ->filter(fn (Jadwal $j) => $j->shift !== null && $j->pengganti_id === null);

            foreach ($shiftAsli as $j) {
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
                    'pengganti_id' => $rencana->id,
                ]);
                $dibuat++;
            }
        }

        if ($dibuat > 0) {
            $rencana->karyawan->user?->notify(new DitunjukJadiPengganti($rencana));
        }

        return $dibuat;
    }

    /** Sinkron seluruh rencana aktif milik satu kasus (dipakai hook approval & UI). */
    public static function sinkronKasus(PengajuanCuti|Karyawan $kasus): int
    {
        $total = 0;
        foreach (KasusPengganti::dari($kasus)->rencana()->aktif()->get() as $rencana) {
            $total += self::sinkronSalinan($rencana);
        }

        return $total;
    }

    /** Sinkron semua lowongan terbuka — dipanggil tiap ada jadwal baru terbentuk. */
    public static function sinkronSemuaLowongan(): int
    {
        $total = 0;
        foreach (PenugasanPengganti::lowongan()->aktif()->get() as $rencana) {
            $total += self::sinkronSalinan($rencana);
        }

        return $total;
    }

    /** Estafet Jalur-1: mulai tanggal X, cakupan berpindah ke $penggantiBaru sampai ujung kasus. */
    public static function alihkan(PengajuanCuti|Karyawan $kasus, Carbon $mulaiBaru, Karyawan $penggantiBaru, User $oleh): void
    {
        $k = KasusPengganti::dari($kasus);
        self::pastikanBisaEstafet($k, $mulaiBaru);

        if ($penggantiBaru->id === $k->digantikan->id) {
            throw new ProsesPenggantiException('Pemohon tak bisa menjadi pengganti dirinya sendiri.');
        }

        $bentrok = self::cekBentrok($penggantiBaru, $kasus, $mulaiBaru, $k->batasAkhir());
        if ($bentrok) {
            throw new ProsesPenggantiException(self::pesanBentrok($bentrok));
        }

        DB::transaction(function () use ($k, $kasus, $mulaiBaru, $penggantiBaru, $oleh) {
            // Lowongan terbuka (tanggal_selesai null) selalu beririsan ke depan.
            $beririsan = $k->rencana()->aktif()->get()
                ->filter(fn (PenugasanPengganti $p) => $p->tanggal_selesai === null
                    || Carbon::parse($p->tanggal_selesai)->gte($mulaiBaru));

            foreach ($beririsan as $p) {
                if (Carbon::parse($p->tanggal_mulai)->lt($mulaiBaru)) {
                    self::hapusJadwalRencana([$p->id], $mulaiBaru);
                    $p->update(['tanggal_selesai' => $mulaiBaru->copy()->subDay()->toDateString()]);
                } else {
                    self::hapusJadwalRencana([$p->id]);
                    $p->delete();
                }
            }

            PenugasanPengganti::create($k->atribut() + [
                'karyawan_id' => $penggantiBaru->id,
                'tanggal_mulai' => $mulaiBaru->toDateString(),
                'tanggal_selesai' => $k->akhir?->toDateString(),
                'status' => StatusPengganti::Aktif,
                'dibuat_oleh' => $oleh->id,
            ]);

            self::sinkronKasus($kasus);
        });
    }

    /** Jalur-2: rekan satu unit yang digantikan mengajukan diri menutup [$mulai, ujung kasus]. */
    public static function ajukanDiri(PengajuanCuti|Karyawan $kasus, Karyawan $pengaju, Carbon $mulai, User $oleh): PenugasanPengganti
    {
        $k = KasusPengganti::dari($kasus);
        self::pastikanBisaEstafet($k, $mulai);

        $digantikan = $k->digantikan;
        if ($pengaju->id === $digantikan->id) {
            throw new ProsesPenggantiException('Pemohon tak bisa menjadi pengganti dirinya sendiri.');
        }
        if ($pengaju->status !== StatusKaryawan::Aktif) {
            throw new ProsesPenggantiException('Karyawan nonaktif tak bisa menjadi pengganti.');
        }
        if ($pengaju->org_unit_id === null || $pengaju->org_unit_id !== $digantikan->org_unit_id) {
            throw new ProsesPenggantiException('Hanya rekan satu unit yang bisa mengajukan diri.');
        }

        $bentrok = self::cekBentrok($pengaju, $kasus, $mulai, $k->batasAkhir());
        if ($bentrok) {
            throw new ProsesPenggantiException(self::pesanBentrok($bentrok));
        }

        return DB::transaction(function () use ($k, $pengaju, $mulai, $oleh) {
            $usulan = PenugasanPengganti::create($k->atribut() + [
                'karyawan_id' => $pengaju->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $k->akhir?->toDateString(),
                'status' => StatusPengganti::Usulan,
                'dibuat_oleh' => $oleh->id,
            ]);

            $k->digantikan->orgUnit?->kepala()?->user?->notify(new UsulanPenggantiMasuk($usulan));

            return $usulan;
        });
    }

    /** Koordinator menyetujui usulan → baris usulan gugur, estafet dijalankan. */
    public static function accUsulan(PenugasanPengganti $usulan, User $koordinator): void
    {
        $kasus = self::kasusDari($usulan);
        self::pastikanKoordinator($usulan, $koordinator);
        if ($usulan->status !== StatusPengganti::Usulan) {
            throw new ProsesPenggantiException('Baris ini bukan usulan.');
        }

        $pengaju = $usulan->karyawan;
        $mulai = Carbon::parse($usulan->tanggal_mulai);

        DB::transaction(function () use ($usulan, $kasus, $pengaju, $mulai, $koordinator) {
            $usulan->delete();
            self::alihkan($kasus, $mulai, $pengaju, $koordinator);
        });
    }

    /** Koordinator menolak usulan → baris dibuang, cakupan tak berubah. */
    public static function tolakUsulan(PenugasanPengganti $usulan, User $koordinator): void
    {
        self::pastikanKoordinator($usulan, $koordinator);
        if ($usulan->status !== StatusPengganti::Usulan) {
            throw new ProsesPenggantiException('Baris ini bukan usulan.');
        }

        $usulan->delete();
    }

    /** Sumber kasus dari sebuah baris rencana (cuti → pengajuannya, lowongan → karyawan digantikan). */
    private static function kasusDari(PenugasanPengganti $rencana): PengajuanCuti|Karyawan
    {
        return $rencana->tipe === TipePengganti::Cuti
            ? $rencana->pengajuan->fresh()
            : $rencana->karyawanDigantikan;
    }

    private static function pastikanKoordinator(PenugasanPengganti $rencana, User $aktor): void
    {
        $kepala = $rencana->karyawanDigantikan?->orgUnit?->kepala();
        if (! $kepala || $kepala->id !== $aktor->karyawan_id) {
            throw new ProsesPenggantiException('Hanya koordinator unit pemohon yang boleh melakukan ini.');
        }
    }

    /**
     * Cuti: hanya untuk pengajuan Disetujui, tanggal harus di dalam masa cuti.
     * Lowongan: cukup masih terbuka; batas atas tak diperiksa (rentangnya terbuka).
     */
    private static function pastikanBisaEstafet(KasusPengganti $k, Carbon $mulai): void
    {
        if ($k->tipe === TipePengganti::Lowongan) {
            if ($k->digantikan->status !== StatusKaryawan::Nonaktif) {
                throw new ProsesPenggantiException('Lowongan sudah ditutup.');
            }
            if ($mulai->lt($k->mulai)) {
                throw new ProsesPenggantiException('Tanggal mulai sebelum karyawan dinonaktifkan.');
            }

            return;
        }

        if ($k->cuti->status !== StatusPengajuanCuti::Disetujui) {
            throw new ProsesPenggantiException('Estafet hanya untuk cuti yang sudah disetujui.');
        }
        if ($mulai->lt($k->mulai) || $mulai->gt($k->akhir)) {
            throw new ProsesPenggantiException('Tanggal mulai estafet di luar masa cuti.');
        }
    }

    /** Cuti batal → salinan jadwal dilepas dan rencana penugasan gugur. */
    public static function bersihkanSaatBatal(PengajuanCuti $cuti): void
    {
        DB::transaction(function () use ($cuti) {
            $ids = $cuti->pengganti()->pluck('id')->all();
            self::hapusJadwalRencana($ids);
            PenugasanPengganti::whereIn('id', $ids)->delete();
        });
    }

    /** Hapus baris jadwal hasil salinan milik rencana tertentu (opsional: hanya sejak tanggal). */
    private static function hapusJadwalRencana(array $ids, ?Carbon $sejak = null): void
    {
        if (! $ids) {
            return;
        }

        Jadwal::whereIn('pengganti_id', $ids)
            ->when($sejak, fn ($q) => $q->whereDate('tanggal', '>=', $sejak->toDateString()))
            ->delete();
    }
}
