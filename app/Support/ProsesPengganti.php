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
    public static function tetapkan(PengajuanCuti $cuti, Karyawan $pengganti, User $oleh): PenugasanPengganti
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
            PenugasanPengganti::whereIn('id', $lama)->delete();

            $baris = PenugasanPengganti::create([
                'tipe' => TipePengganti::Cuti,
                'pengajuan_cuti_id' => $cuti->id,
                'karyawan_digantikan_id' => $cuti->karyawan_id,
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
                    ->filter(fn (Jadwal $j) => $j->shift !== null && $j->pengganti_id === null);

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
                        'pengganti_id' => $rencana->id,
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
                ->filter(fn (PenugasanPengganti $p) => Carbon::parse($p->tanggal_selesai)->gte($mulaiBaru));

            foreach ($beririsan as $p) {
                if (Carbon::parse($p->tanggal_mulai)->lt($mulaiBaru)) {
                    self::hapusJadwalRencana([$p->id], $mulaiBaru);
                    $p->update(['tanggal_selesai' => $mulaiBaru->copy()->subDay()->toDateString()]);
                } else {
                    self::hapusJadwalRencana([$p->id]);
                    $p->delete();
                }
            }

            PenugasanPengganti::create([
                'tipe' => TipePengganti::Cuti,
                'pengajuan_cuti_id' => $cuti->id,
                'karyawan_digantikan_id' => $cuti->karyawan_id,
                'karyawan_id' => $penggantiBaru->id,
                'tanggal_mulai' => $mulaiBaru->toDateString(),
                'tanggal_selesai' => $akhir->toDateString(),
                'status' => StatusPengganti::Aktif,
                'dibuat_oleh' => $oleh->id,
            ]);

            self::generateSaatDisetujui($cuti->fresh());
        });
    }

    /** Jalur-2: rekan satu unit pemohon mengajukan diri menutup [$mulai, akhir cuti]. */
    public static function ajukanDiri(PengajuanCuti $cuti, Karyawan $pengaju, Carbon $mulai, User $oleh): PenugasanPengganti
    {
        self::pastikanBisaEstafet($cuti, $mulai);

        $pemohon = $cuti->karyawan;
        if ($pengaju->id === $pemohon->id) {
            throw new ProsesPenggantiException('Pemohon tak bisa menjadi pengganti dirinya sendiri.');
        }
        if ($pengaju->status !== StatusKaryawan::Aktif) {
            throw new ProsesPenggantiException('Karyawan nonaktif tak bisa menjadi pengganti.');
        }
        if ($pengaju->org_unit_id === null || $pengaju->org_unit_id !== $pemohon->org_unit_id) {
            throw new ProsesPenggantiException('Hanya rekan satu unit yang bisa mengajukan diri.');
        }

        $akhir = Carbon::parse($cuti->tanggal_selesai);
        $bentrok = self::cekBentrok($pengaju, $cuti, $mulai, $akhir);
        if ($bentrok) {
            throw new ProsesPenggantiException(self::pesanBentrok($bentrok));
        }

        return DB::transaction(function () use ($cuti, $pengaju, $mulai, $akhir, $oleh) {
            $usulan = PenugasanPengganti::create([
                'tipe' => TipePengganti::Cuti,
                'pengajuan_cuti_id' => $cuti->id,
                'karyawan_digantikan_id' => $cuti->karyawan_id,
                'karyawan_id' => $pengaju->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $akhir->toDateString(),
                'status' => StatusPengganti::Usulan,
                'dibuat_oleh' => $oleh->id,
            ]);

            $cuti->karyawan->orgUnit?->kepala()?->user?->notify(new UsulanPenggantiMasuk($usulan));

            return $usulan;
        });
    }

    /** Koordinator menyetujui usulan → baris usulan gugur, estafet dijalankan. */
    public static function accUsulan(PenugasanPengganti $usulan, User $koordinator): void
    {
        $cuti = $usulan->pengajuan;
        self::pastikanKoordinator($cuti, $koordinator);
        if ($usulan->status !== StatusPengganti::Usulan) {
            throw new ProsesPenggantiException('Baris ini bukan usulan.');
        }

        $pengaju = $usulan->karyawan;
        $mulai = Carbon::parse($usulan->tanggal_mulai);

        DB::transaction(function () use ($usulan, $cuti, $pengaju, $mulai, $koordinator) {
            $usulan->delete();
            self::alihkan($cuti->fresh(), $mulai, $pengaju, $koordinator);
        });
    }

    /** Koordinator menolak usulan → baris dibuang, cakupan tak berubah. */
    public static function tolakUsulan(PenugasanPengganti $usulan, User $koordinator): void
    {
        self::pastikanKoordinator($usulan->pengajuan, $koordinator);
        if ($usulan->status !== StatusPengganti::Usulan) {
            throw new ProsesPenggantiException('Baris ini bukan usulan.');
        }

        $usulan->delete();
    }

    private static function pastikanKoordinator(PengajuanCuti $cuti, User $aktor): void
    {
        $kepala = $cuti->karyawan->orgUnit?->kepala();
        if (! $kepala || $kepala->id !== $aktor->karyawan_id) {
            throw new ProsesPenggantiException('Hanya koordinator unit pemohon yang boleh melakukan ini.');
        }
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
