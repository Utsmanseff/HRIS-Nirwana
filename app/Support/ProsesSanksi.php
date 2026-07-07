<?php

namespace App\Support;

use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
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
    /** Setujui tahap antara. Override tingkat (opsional) + nomor wajib bila tahap HRD. Final → terbit(). */
    public static function setujui(ApprovalSanksi $step, User $aktor, ?string $catatan = null, ?int $tingkatBaru = null, ?string $nomor = null): void
    {
        DB::transaction(function () use ($step, $aktor, $catatan, $tingkatBaru, $nomor) {
            $sanksi = SanksiDisiplin::whereKey($step->sanksi_id)->lockForUpdate()->firstOrFail();
            self::pastikanTahapAktif($sanksi, $step);
            self::pastikanWewenang($step, $aktor);

            $berikut = self::tahapBerikut($sanksi, $step);
            if (! $berikut) {
                throw new ProsesSanksiException('Tahap final (Direktur): gunakan Terbitkan.');
            }

            $catatan = self::terapkanTingkat($sanksi, $tingkatBaru, $catatan);

            if ($step->peran === PeranApproval::Hrd) {
                self::setNomor($sanksi, $nomor);
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

    /** Terapkan override tingkat bila diisi & beda; kembalikan catatan (+jejak perubahan). */
    protected static function terapkanTingkat(SanksiDisiplin $sanksi, ?int $tingkatBaru, ?string $catatan): ?string
    {
        if ($tingkatBaru === null) {
            return $catatan;
        }
        $baru = TingkatSanksi::tryFrom($tingkatBaru);
        if (! $baru) {
            throw new ProsesSanksiException('Tingkat tidak valid.');
        }
        if ($baru === $sanksi->tingkat) {
            return $catatan;
        }
        $jejak = "Tingkat diubah {$sanksi->tingkat->label()} → {$baru->label()}.";
        $sanksi->update(['tingkat' => $baru]);

        return trim(($catatan ? $catatan.' ' : '').$jejak);
    }

    /** Set nomor surat manual (wajib, unik). */
    protected static function setNomor(SanksiDisiplin $sanksi, ?string $nomor): void
    {
        $nomor = trim((string) $nomor);
        if ($nomor === '') {
            throw new ProsesSanksiException('Nomor surat wajib diisi.');
        }
        if (SanksiDisiplin::where('nomor_surat', $nomor)->where('id', '!=', $sanksi->id)->exists()) {
            throw new ProsesSanksiException('Nomor surat sudah dipakai.');
        }
        $sanksi->update(['nomor_surat' => $nomor]);
    }

    /** Terbitkan sanksi (tahap final = Direktur). Nomor: pakai yang diisi HRD; kalau kosong wajib $nomor. */
    public static function terbit(ApprovalSanksi $step, User $aktor, ?string $nomor = null, ?string $catatan = null, ?int $tingkatBaru = null): void
    {
        DB::transaction(function () use ($step, $aktor, $nomor, $catatan, $tingkatBaru) {
            $sanksi = SanksiDisiplin::whereKey($step->sanksi_id)->lockForUpdate()->firstOrFail();
            self::pastikanTahapAktif($sanksi, $step);
            self::pastikanWewenang($step, $aktor);

            if (self::tahapBerikut($sanksi, $step)) {
                throw new ProsesSanksiException('Masih ada tahap sebelum penerbitan.');
            }

            $catatan = self::terapkanTingkat($sanksi, $tingkatBaru, $catatan);

            if (trim((string) $sanksi->nomor_surat) === '') {
                self::setNomor($sanksi, $nomor);
            }

            $step->update([
                'status' => StatusApproval::Setuju,
                'catatan' => $catatan,
                'acted_at' => Carbon::now(),
            ]);

            $terbit = Carbon::today();
            $sanksi->update([
                'status' => StatusSanksi::Diterbitkan,
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
