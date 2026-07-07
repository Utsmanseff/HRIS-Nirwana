<?php

namespace App\Support;

use App\Enums\StatusSanksi;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RekapDisiplin
{
    /**
     * Query sanksi ter-filter (relasi + urutan) untuk tabel & ekspor.
     *
     * @param  array{dari?:string,sampai?:string,unit_id?:int|string|null,tingkat?:int|string|null,status?:string|null}  $f
     */
    public static function query(array $f): Builder
    {
        return SanksiDisiplin::query()
            ->with(['karyawan.orgUnit', 'pengusul'])
            ->when(! empty($f['dari']), fn ($q) => $q->whereDate('tanggal_kejadian', '>=', $f['dari']))
            ->when(! empty($f['sampai']), fn ($q) => $q->whereDate('tanggal_kejadian', '<=', $f['sampai']))
            ->when(! empty($f['unit_id']), fn ($q) => $q->whereHas('karyawan',
                fn ($k) => $k->whereIn('org_unit_id', OrgUnit::denganTurunan((int) $f['unit_id']))))
            ->when(! empty($f['tingkat']), fn ($q) => $q->where('tingkat', $f['tingkat']))
            ->when(! empty($f['status']) && $f['status'] !== 'semua', fn ($q) => $q->where('status', $f['status']))
            ->orderByDesc('tanggal_kejadian')
            ->orderByDesc('id');
    }

    /**
     * Jumlah per status (abaikan filter status). Semua status hadir (default 0).
     *
     * @return array<string,int>
     */
    public static function hitungStatus(array $f): array
    {
        $dasar = array_fill_keys(
            array_map(fn ($s) => $s->value, StatusSanksi::cases()),
            0,
        );

        $fTanpaStatus = $f;
        unset($fTanpaStatus['status']);

        $hitung = self::query($fTanpaStatus)
            ->reorder()
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        foreach ($hitung as $status => $n) {
            $dasar[$status] = (int) $n;
        }

        return $dasar;
    }

    /** @return Collection<int,SanksiDisiplin> */
    public static function daftarSanksi(array $f): Collection
    {
        return self::query($f)->get();
    }

    /** Total usulan menunggu (diajukan+diproses) seluruh org, tanpa filter periode. */
    public static function jumlahPendingOrgWide(): int
    {
        return SanksiDisiplin::query()
            ->whereIn('status', [StatusSanksi::Diajukan->value, StatusSanksi::Diproses->value])
            ->count();
    }

    /** Total sanksi diterbitkan seluruh org, tanpa filter periode. */
    public static function jumlahDiterbitkanOrgWide(): int
    {
        return SanksiDisiplin::query()
            ->where('status', StatusSanksi::Diterbitkan->value)
            ->count();
    }
}
