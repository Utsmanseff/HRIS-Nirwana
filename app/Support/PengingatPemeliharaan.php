<?php

namespace App\Support;

use App\Enums\SeverityPengingat;
use App\Enums\StatusAset;
use App\Models\JadwalPemeliharaan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Pengingat pemeliharaan derived (tanpa kolom status). Satu per jadwal AKTIF pada aset
 *  non-afkir yang berikutnya dalam/melewati ambang H-14. Pola PengingatSip. */
class PengingatPemeliharaan
{
    private const AMBANG_HARI = 14;

    public function __construct(
        public JadwalPemeliharaan $jadwal,
        public SeverityPengingat $severity,
        public int $sisaHari, // negatif = lewat
    ) {}

    /**
     * @param  list<string>|null  $timNilai  filter nilai TimTeknis; null = semua tim
     */
    public static function semua(?array $timNilai = null, ?Carbon $hariIni = null): Collection
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();

        return JadwalPemeliharaan::query()
            ->where('aktif', true)
            ->whereHas('aset', function ($a) use ($timNilai) {
                $a->where('status', '!=', StatusAset::Afkir->value);
                if ($timNilai !== null) {
                    $a->whereHas('kategori', fn ($k) => $k->whereIn('tim', $timNilai));
                }
            })
            ->with('aset.kategori')
            ->get()
            ->map(fn (JadwalPemeliharaan $j) => self::untuk($j, $hariIni))
            ->filter()->values();
    }

    public static function untuk(JadwalPemeliharaan $j, ?Carbon $hariIni = null): ?self
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();
        $berikutnya = $j->berikutnya();

        if ($berikutnya === null) {
            // Belum pernah dilakukan → perlu dijadwalkan (butuh perhatian).
            return new self($j, SeverityPengingat::AkanBerakhir, 0);
        }

        $sisaHari = (int) $hariIni->diffInDays($berikutnya->copy()->startOfDay(), false);
        if ($sisaHari < 0) {
            return new self($j, SeverityPengingat::Terlewat, $sisaHari);
        }
        if ($sisaHari <= self::AMBANG_HARI) {
            return new self($j, SeverityPengingat::AkanBerakhir, $sisaHari);
        }

        return null;
    }
}
