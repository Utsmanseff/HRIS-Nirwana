<?php

// app/Support/SaldoCuti.php

namespace App\Support;

use App\Enums\KodeJenisCuti;
use App\Enums\StatusPengajuanCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PenyesuaianSaldo;
use Illuminate\Support\Carbon;

class SaldoCuti
{
    public const JATAH_DASAR = 12;

    public function __construct(private Karyawan $karyawan) {}

    public static function untuk(Karyawan $karyawan): self
    {
        return new self($karyawan);
    }

    /**
     * Awal periode-cuti aktif yang memuat $acuan.
     * Anchor = kontrak nyata TERBARU yang berlaku (reset ikut siklus kontrak terbaru).
     * Null bila belum eligible (masa kerja < 1 tahun / tanpa kontrak nyata).
     */
    public function periodeMulai(?Carbon $acuan = null): ?Carbon
    {
        $acuan = ($acuan ?? Carbon::today())->copy()->startOfDay();
        if (! $this->eligible($acuan)) {
            return null;
        }

        $anchor = $this->karyawan->anchorPeriodeCuti($acuan) ?? $this->karyawan->anchorMasaKerja();
        if (! $anchor) {
            return null;
        }

        // K terbesar dengan (anchor + K tahun) <= acuan. K>=0 (eligibility sudah dijamin masa kerja).
        // Pakai addYears (aman leap year), hindari float.
        $k = 0;
        while ($anchor->copy()->addYears($k + 1)->lessThanOrEqualTo($acuan)) {
            $k++;
        }

        return $anchor->copy()->addYears($k);
    }

    public function periodeSelesai(?Carbon $acuan = null): ?Carbon
    {
        return $this->periodeMulai($acuan)?->copy()->addYear();
    }

    /**
     * Daftar periode_mulai yang boleh disesuaikan HRD (cegah baris yatim):
     * periode aktif sekarang + periode berikutnya. Kosong bila belum eligible.
     *
     * @return list<Carbon>
     */
    public function periodeValid(?Carbon $acuan = null): array
    {
        $mulai = $this->periodeMulai($acuan);
        if (! $mulai) {
            return [];
        }

        return [$mulai->copy(), $mulai->copy()->addYear()];
    }

    /** Eligible = masa kerja (kontrak nyata terlama) >= 1 tahun pada $acuan. Tak reset saat kontrak diperbarui. */
    public function eligible(?Carbon $acuan = null): bool
    {
        $mulai = $this->karyawan->anchorMasaKerja();
        if (! $mulai) {
            return false;
        }
        $acuan = ($acuan ?? Carbon::today())->copy()->startOfDay();

        return $mulai->copy()->addYear()->lessThanOrEqualTo($acuan);
    }

    /** Jatah = 12 + Σ penyesuaian(periode aktif). 0 bila tak eligible. */
    public function jatah(?Carbon $acuan = null): int
    {
        $mulai = $this->periodeMulai($acuan);
        if (! $mulai) {
            return 0;
        }
        $delta = (int) PenyesuaianSaldo::query()
            ->where('karyawan_id', $this->karyawan->id)
            ->whereDate('periode_mulai', $mulai->toDateString())
            ->sum('delta');

        return self::JATAH_DASAR + $delta;
    }

    /** Cuti tahunan disetujui, tanggal_mulai dalam periode aktif. */
    public function terpakai(?Carbon $acuan = null): int
    {
        return $this->jumlahHari($acuan, [StatusPengajuanCuti::Disetujui->value]);
    }

    /** Cuti tahunan diajukan|diproses, tanggal_mulai dalam periode aktif. */
    public function pending(?Carbon $acuan = null): int
    {
        return $this->jumlahHari($acuan, [StatusPengajuanCuti::Diajukan->value, StatusPengajuanCuti::Diproses->value]);
    }

    public function efektif(?Carbon $acuan = null): int
    {
        if (! $this->eligible($acuan)) {
            return 0;
        }

        return $this->jatah($acuan) - $this->terpakai($acuan) - $this->pending($acuan);
    }

    /** @param string[] $status */
    private function jumlahHari(?Carbon $acuan, array $status): int
    {
        $mulai = $this->periodeMulai($acuan);
        if (! $mulai) {
            return 0;
        }
        $selesai = $mulai->copy()->addYear();

        return (int) PengajuanCuti::query()
            ->where('karyawan_id', $this->karyawan->id)
            ->whereHas('jenisCuti', fn ($q) => $q->where('kode', KodeJenisCuti::CutiTahunan->value))
            ->whereIn('status', $status)
            ->where('tanggal_mulai', '>=', $mulai->toDateString())
            ->where('tanggal_mulai', '<', $selesai->toDateString())
            ->sum('jumlah_hari');
    }
}
