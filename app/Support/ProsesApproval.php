<?php

namespace App\Support;

use App\Enums\KodeJenisCuti;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\ApprovalCuti;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Notifications\CutiDibatalkan;
use App\Notifications\CutiDisetujui;
use App\Notifications\CutiDitolak;
use App\Notifications\CutiPerluPersetujuan;
use App\Support\SaldoCuti;
use App\Support\SuratCuti;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProsesApproval
{
    /** Setujui tahap aktif. Maju ke tahap berikut, atau final (disetujui) bila tahap terakhir. */
    public static function setujui(ApprovalCuti $step, User $aktor, ?string $catatan = null): void
    {
        DB::transaction(function () use ($step, $aktor, $catatan) {
            $pengajuan = PengajuanCuti::whereKey($step->pengajuan_cuti_id)->lockForUpdate()->firstOrFail();
            self::pastikanTahapAktif($pengajuan, $step);
            self::pastikanWewenang($pengajuan, $step, $aktor);

            $step->update([
                'status' => StatusApproval::Setuju,
                'catatan' => $catatan,
                'acted_at' => Carbon::now(),
            ]);

            $berikut = $pengajuan->approval()
                ->where('status', StatusApproval::Menunggu)
                ->where('urutan', '>', $step->urutan)
                ->orderBy('urutan')
                ->first();

            if ($berikut) {
                $pengajuan->update(['status' => StatusPengajuanCuti::Diproses]);
                $berikut->approver->user?->notify(new CutiPerluPersetujuan($pengajuan));

                return;
            }

            // Tahap terakhir → final. Status dulu, baru generate: surat harus dibikin dari
            // pengajuan yang statusnya sudah Disetujui (pola sama dgn ProsesSanksi::terbit).
            self::pastikanJatahCukup($pengajuan);
            $pengajuan->update(['status' => StatusPengajuanCuti::Disetujui]);
            $pengajuan->update(['surat_path' => SuratCuti::generate($pengajuan->fresh())]);
            $pengajuan->karyawan->user?->notify(new CutiDisetujui($pengajuan));
        });
    }

    /** Tolak tahap aktif → pengajuan ditolak (batal total). Catatan wajib. */
    public static function tolak(ApprovalCuti $step, User $aktor, string $catatan): void
    {
        DB::transaction(function () use ($step, $aktor, $catatan) {
            $pengajuan = PengajuanCuti::whereKey($step->pengajuan_cuti_id)->lockForUpdate()->firstOrFail();
            self::pastikanTahapAktif($pengajuan, $step);
            self::pastikanWewenang($pengajuan, $step, $aktor);

            $step->update([
                'status' => StatusApproval::Tolak,
                'catatan' => $catatan,
                'acted_at' => Carbon::now(),
            ]);
            $pengajuan->update(['status' => StatusPengajuanCuti::Ditolak]);
            $pengajuan->karyawan->user?->notify(new CutiDitolak($pengajuan, $catatan));
        });
    }

    private static function pastikanTahapAktif(PengajuanCuti $pengajuan, ApprovalCuti $step): void
    {
        $aktif = $pengajuan->tahapAktif();
        if (! $aktif || $aktif->id !== $step->id) {
            throw new ProsesApprovalException('Tahap ini bukan tahap aktif.');
        }
    }

    private static function pastikanWewenang(PengajuanCuti $pengajuan, ApprovalCuti $step, User $aktor): void
    {
        if ($step->approver_id === $aktor->karyawan_id) {
            return;
        }
        // Self-approve HRD: pemohon ber-role HRD boleh acc tahap final pengajuannya sendiri.
        if (self::hrdSelfApprove($pengajuan, $step, $aktor)) {
            return;
        }
        throw new ProsesApprovalException('Anda bukan approver tahap ini.');
    }

    private static function hrdSelfApprove(PengajuanCuti $pengajuan, ApprovalCuti $step, User $aktor): bool
    {
        if ($aktor->karyawan_id !== $pengajuan->karyawan_id || ! $aktor->hasRole(Role::Hrd->value)) {
            return false;
        }
        $adaBerikut = $pengajuan->approval()
            ->where('status', StatusApproval::Menunggu)
            ->where('urutan', '>', $step->urutan)
            ->exists();

        return ! $adaBerikut; // hanya bila tahap terakhir
    }

    /** Guard defensif: total cuti-tahunan disetujui tak boleh melampaui jatah periode. */
    private static function pastikanJatahCukup(PengajuanCuti $pengajuan): void
    {
        $pengajuan->loadMissing('jenisCuti', 'karyawan');
        if ($pengajuan->jenisCuti->kode !== KodeJenisCuti::CutiTahunan) {
            return;
        }
        $acuan = Carbon::parse($pengajuan->tanggal_mulai);
        $saldo = SaldoCuti::untuk($pengajuan->karyawan);
        $sisa = $saldo->jatah($acuan) - $saldo->terpakai($acuan); // terpakai = disetujui, belum termasuk ini
        if ($pengajuan->jumlah_hari > $sisa) {
            throw new ProsesApprovalException("Jatah tak cukup: sisa {$sisa} hari, diminta {$pengajuan->jumlah_hari}.");
        }
    }

    /** HRD membatalkan cuti yang sudah disetujui → jatah balik otomatis (derived). */
    public static function batalkanOlehHrd(PengajuanCuti $pengajuan, User $hrd, string $alasan): void
    {
        if (! $hrd->hasRole(Role::Hrd->value)) {
            throw new ProsesApprovalException('Hanya HRD yang boleh membatalkan cuti disetujui.');
        }

        DB::transaction(function () use ($pengajuan, $hrd, $alasan) {
            $terkunci = PengajuanCuti::whereKey($pengajuan->id)->lockForUpdate()->firstOrFail();
            if ($terkunci->status !== StatusPengajuanCuti::Disetujui) {
                throw new ProsesApprovalException('Hanya cuti berstatus disetujui yang bisa dibatalkan.');
            }
            $terkunci->update([
                'status' => StatusPengajuanCuti::Dibatalkan,
                'dibatalkan_oleh' => $hrd->id,
                'alasan_batal' => $alasan,
            ]);
            $terkunci->karyawan->user?->notify(new CutiDibatalkan($terkunci, $alasan));
        });
    }
}
