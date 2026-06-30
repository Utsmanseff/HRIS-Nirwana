<?php

// app/Support/PengingatKontrak.php

namespace App\Support;

use App\Enums\SeverityPengingat;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Pengingat kontrak derived (tanpa kolom tambahan). Satu per karyawan AKTIF yang
 *  baris kontrak TERAKHIR-nya berbatas waktu dan dalam/melewati threshold. */
class PengingatKontrak
{
    public function __construct(
        public Karyawan $karyawan,
        public Kontrak $kontrak,
        public SeverityPengingat $severity,
        public int $sisaHari, // negatif = sudah lewat
    ) {}

    public static function semua(?Carbon $hariIni = null): Collection
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();

        return Karyawan::query()
            ->where('status', StatusKaryawan::Aktif->value)
            ->with('kontrak')->get()
            ->map(fn (Karyawan $k) => self::untuk($k, $hariIni))
            ->filter()->values();
    }

    public static function untuk(Karyawan $k, ?Carbon $hariIni = null): ?self
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();
        $terakhir = $k->kontrak->sortByDesc('tanggal_mulai')->sortByDesc('id')->first();
        if (! $terakhir || ! $terakhir->jenis->berbatasWaktu() || ! $terakhir->tanggal_akhir) {
            return null;
        }
        $akhir = $terakhir->tanggal_akhir->copy()->startOfDay();
        $sisaHari = (int) $hariIni->diffInDays($akhir, false);
        $threshold = $terakhir->jenis->thresholdHari() ?? 0;
        if ($sisaHari < 0) {
            return new self($k, $terakhir, SeverityPengingat::Terlewat, $sisaHari);
        }
        if ($sisaHari <= $threshold) {
            return new self($k, $terakhir, SeverityPengingat::AkanBerakhir, $sisaHari);
        }

        return null;
    }
}
