<?php

namespace App\Support;

use App\Enums\StatusApproval;
use App\Enums\StatusSanksi;
use App\Models\ApprovalSanksi;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Notifications\SanksiDiterbitkan;
use App\Notifications\SanksiDitolak;
use App\Notifications\SanksiPerluPersetujuan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProsesSanksi
{
    /** Setujui tahap antara (bukan final). Maju ke tahap berikut + notif. Final → pakai terbit(). */
    public static function setujui(ApprovalSanksi $step, User $aktor, ?string $catatan = null): void
    {
        DB::transaction(function () use ($step, $aktor, $catatan) {
            $sanksi = SanksiDisiplin::whereKey($step->sanksi_id)->lockForUpdate()->firstOrFail();
            self::pastikanTahapAktif($sanksi, $step);
            self::pastikanWewenang($step, $aktor);

            $berikut = self::tahapBerikut($sanksi, $step);
            if (! $berikut) {
                throw new ProsesSanksiException('Tahap final (HRD): gunakan Terbitkan (butuh nomor surat).');
            }

            $step->update([
                'status' => StatusApproval::Setuju,
                'catatan' => $catatan,
                'acted_at' => Carbon::now(),
            ]);
            $sanksi->update(['status' => StatusSanksi::Diproses]);
            $berikut->approver->user?->notify(new SanksiPerluPersetujuan($sanksi));
        });
    }

    /**
     * Terbitkan sanksi (tahap final HRD). Butuh nomor surat manual (unik). Set tanggal + generate surat + notif karyawan.
     */
    public static function terbit(ApprovalSanksi $step, User $aktor, string $nomor, ?string $catatan = null): void
    {
        DB::transaction(function () use ($step, $aktor, $nomor, $catatan) {
            $sanksi = SanksiDisiplin::whereKey($step->sanksi_id)->lockForUpdate()->firstOrFail();
            self::pastikanTahapAktif($sanksi, $step);
            self::pastikanWewenang($step, $aktor);

            if (self::tahapBerikut($sanksi, $step)) {
                throw new ProsesSanksiException('Masih ada tahap sebelum penerbitan.');
            }

            $nomor = trim($nomor);
            if ($nomor === '') {
                throw new ProsesSanksiException('Nomor surat wajib diisi.');
            }
            if (SanksiDisiplin::where('nomor_surat', $nomor)->where('id', '!=', $sanksi->id)->exists()) {
                throw new ProsesSanksiException('Nomor surat sudah dipakai.');
            }

            $step->update([
                'status' => StatusApproval::Setuju,
                'catatan' => $catatan,
                'acted_at' => Carbon::now(),
            ]);

            $terbit = Carbon::today();
            $sanksi->update([
                'status' => StatusSanksi::Diterbitkan,
                'nomor_surat' => $nomor,
                'tanggal_terbit' => $terbit,
                'berlaku_sampai' => $terbit->copy()->addMonths(6),
                'diterbitkan_oleh' => $aktor->id,
            ]);

            $sanksi->update(['surat_path' => SuratSanksi::generate($sanksi->fresh())]);

            $sanksi->karyawan->user?->notify(new SanksiDiterbitkan($sanksi));
        });
    }

    /** Tolak tahap aktif → sanksi Ditolak (batal total). Catatan wajib. Notif pengusul. */
    public static function tolak(ApprovalSanksi $step, User $aktor, string $catatan): void
    {
        DB::transaction(function () use ($step, $aktor, $catatan) {
            $sanksi = SanksiDisiplin::whereKey($step->sanksi_id)->lockForUpdate()->firstOrFail();
            self::pastikanTahapAktif($sanksi, $step);
            self::pastikanWewenang($step, $aktor);

            $step->update([
                'status' => StatusApproval::Tolak,
                'catatan' => $catatan,
                'acted_at' => Carbon::now(),
            ]);
            $sanksi->update([
                'status' => StatusSanksi::Ditolak,
                'alasan_tolak' => $catatan,
            ]);
            $sanksi->pengusul->user?->notify(new SanksiDitolak($sanksi, $catatan));
        });
    }

    protected static function tahapBerikut(SanksiDisiplin $sanksi, ApprovalSanksi $step): ?ApprovalSanksi
    {
        return $sanksi->approval()
            ->where('status', StatusApproval::Menunggu)
            ->where('urutan', '>', $step->urutan)
            ->orderBy('urutan')
            ->first();
    }

    protected static function pastikanTahapAktif(SanksiDisiplin $sanksi, ApprovalSanksi $step): void
    {
        $aktif = $sanksi->tahapAktif();
        if (! $aktif || $aktif->id !== $step->id) {
            throw new ProsesSanksiException('Tahap ini bukan tahap aktif.');
        }
    }

    protected static function pastikanWewenang(ApprovalSanksi $step, User $aktor): void
    {
        if ($step->approver_id !== $aktor->karyawan_id) {
            throw new ProsesSanksiException('Anda bukan approver tahap ini.');
        }
    }
}
