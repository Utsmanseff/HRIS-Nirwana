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
     * Awal periode-cuti aktif (anchor + N tahun, N>=1) yang memuat $acuan.
     * Null bila tak eligible (belum genap 1 tahun sejak PKWT pertama / tanpa PKWT).
     */
    public function periodeMulai(?Carbon $acuan = null): ?Carbon
    {
        $anchor = $this->karyawan->anchorCutiTahunan();
        if (! $anchor) {
            return null;
        }
        $acuan = ($acuan ?? Carbon::today())->copy()->startOfDay();

        // N terbesar dengan (anchor + N tahun) <= acuan. Pakai addYears (aman leap year), hindari float.
        $n = 0;
        while ($anchor->copy()->addYears($n + 1)->lessThanOrEqualTo($acuan)) {
            $n++;
        }

        return $n >= 1 ? $anchor->copy()->addYears($n) : null;
    }

    public function periodeSelesai(?Carbon $acuan = null): ?Carbon
    {
        return $this->periodeMulai($acuan)?->copy()->addYear();
    }

    public function eligible(?Carbon $acuan = null): bool
    {
        return $this->periodeMulai($acuan) !== null;
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
