<?php

namespace App\Support;

use App\Enums\TipePengganti;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Normalisasi "apa yang harus ditutup": sebuah pengajuan cuti, atau seorang
 * karyawan nonaktif (lowongan). Dipakai internal ProsesPengganti supaya
 * aturannya satu jalur, tanpa cabang per tipe di tiap method.
 */
final class KasusPengganti
{
    private function __construct(
        public readonly TipePengganti $tipe,
        public readonly Karyawan $digantikan,
        public readonly ?PengajuanCuti $cuti,
        public readonly Carbon $mulai,
        public readonly ?Carbon $akhir,     // null = terbuka
    ) {}

    public static function dari(PengajuanCuti|Karyawan $sumber): self
    {
        if ($sumber instanceof PengajuanCuti) {
            return new self(
                TipePengganti::Cuti,
                $sumber->karyawan,
                $sumber,
                Carbon::parse($sumber->tanggal_mulai),
                Carbon::parse($sumber->tanggal_selesai),
            );
        }

        return new self(
            TipePengganti::Lowongan,
            $sumber,
            null,
            $sumber->tanggal_nonaktif ? Carbon::parse($sumber->tanggal_nonaktif) : now()->startOfDay(),
            null,
        );
    }

    /** Baris rencana milik kasus ini. */
    public function rencana(): Builder
    {
        if ($this->tipe === TipePengganti::Cuti) {
            return PenugasanPengganti::query()->where('pengajuan_cuti_id', $this->cuti->id);
        }

        return PenugasanPengganti::query()->lowongan()
            ->where('karyawan_digantikan_id', $this->digantikan->id);
    }

    /**
     * Ujung rentang yang bisa dipakai untuk menyalin/memeriksa bentrok.
     * Cuti: akhir masa cuti. Lowongan terbuka: tanggal jadwal terakhir si
     * digantikan (null bila jejaknya sudah habis → tak ada yang perlu ditutup).
     */
    public function batasAkhir(): ?Carbon
    {
        if ($this->akhir) {
            return $this->akhir;
        }

        $terakhir = Jadwal::where('karyawan_id', $this->digantikan->id)->max('tanggal');

        return $terakhir ? Carbon::parse($terakhir)->startOfDay() : null;
    }

    /** Atribut kunci untuk PenugasanPengganti::create(). */
    public function atribut(): array
    {
        return [
            'tipe' => $this->tipe,
            'pengajuan_cuti_id' => $this->cuti?->id,
            'karyawan_digantikan_id' => $this->digantikan->id,
        ];
    }
}
