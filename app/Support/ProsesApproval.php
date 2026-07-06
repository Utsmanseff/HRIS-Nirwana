<?php

namespace App\Support;

use App\Enums\KodeJenisCuti;
use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\ApprovalCuti;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Notifications\CutiDisetujui;
use App\Notifications\CutiPerluPersetujuan;
use App\Support\SaldoCuti;
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

            // Tahap terakhir → final.
            self::pastikanJatahCukup($pengajuan);
            $pengajuan->update(['status' => StatusPengajuanCuti::Disetujui]);
            $pengajuan->karyawan->user?->notify(new CutiDisetujui($pengajuan));
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
        if ($step->approver_id !== $aktor->karyawan_id) {
            throw new ProsesApprovalException('Anda bukan approver tahap ini.');
        }
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
}
