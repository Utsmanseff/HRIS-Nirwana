<?php

namespace App\Support;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EskalasiSanksi
{
    /**
     * Sanksi aktif = status diterbitkan & berlaku_sampai >= hari ini (dicabut otomatis keluar).
     *
     * @return Collection<int, SanksiDisiplin>
     */
    public static function sanksiAktif(Karyawan $kar): Collection
    {
        return SanksiDisiplin::query()
            ->where('karyawan_id', $kar->id)
            ->where('status', StatusSanksi::Diterbitkan->value)
            ->whereNotNull('berlaku_sampai')
            ->whereDate('berlaku_sampai', '>=', Carbon::today())
            ->get();
    }

    /** Saran tingkat berikut dari sanksi aktif tertinggi (spec §3). Tanpa aktif → Teguran1; SP3 → mentok (tetap SP3). */
    public static function sarankan(Karyawan $kar): TingkatSanksi
    {
        $tertinggi = self::sanksiAktif($kar)
            ->map(fn (SanksiDisiplin $s) => $s->tingkat->value)
            ->max();

        if ($tertinggi === null) {
            return TingkatSanksi::Teguran1;
        }

        $current = TingkatSanksi::from($tertinggi);

        return $current->berikutnya() ?? TingkatSanksi::Sp3;
    }
}
