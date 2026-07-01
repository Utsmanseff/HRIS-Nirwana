<?php

// app/Support/PengingatSip.php

namespace App\Support;

use App\Enums\SeverityPengingat;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Pengingat SIP derived (tanpa kolom status). Satu per karyawan AKTIF yang punya
 *  sip_berlaku_akhir dan berada dalam/melewati ambang H-30. Pola identik PengingatKontrak. */
class PengingatSip
{
    private const AMBANG_HARI = 30;

    public function __construct(
        public Karyawan $karyawan,
        public SeverityPengingat $severity,
        public int $sisaHari, // negatif = sudah lewat
    ) {}

    public static function semua(?Carbon $hariIni = null): Collection
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();

        return Karyawan::query()
            ->where('status', StatusKaryawan::Aktif->value)
            ->whereNotNull('sip_berlaku_akhir')
            ->get()
            ->map(fn (Karyawan $k) => self::untuk($k, $hariIni))
            ->filter()->values();
    }

    public static function untuk(Karyawan $k, ?Carbon $hariIni = null): ?self
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();
        if ($k->status !== StatusKaryawan::Aktif || ! $k->sip_berlaku_akhir) {
            return null;
        }
        $akhir = $k->sip_berlaku_akhir->copy()->startOfDay();
        $sisaHari = (int) $hariIni->diffInDays($akhir, false);
        if ($sisaHari < 0) {
            return new self($k, SeverityPengingat::Terlewat, $sisaHari);
        }
        if ($sisaHari <= self::AMBANG_HARI) {
            return new self($k, SeverityPengingat::AkanBerakhir, $sisaHari);
        }

        return null;
    }
}
