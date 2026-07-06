<?php

namespace App\Support;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\ApprovalCuti;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Notifications\CutiPerluPersetujuan;
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

            // Tahap terakhir → final (implementasi lengkap di Task 4).
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
}
