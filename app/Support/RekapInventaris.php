<?php

namespace App\Support;

use App\Enums\StatusAset;
use App\Models\Aset;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RekapInventaris
{
    /**
     * @param  array{tim?:list<string>|null,kategori_id?:int|null,unit_id?:int|null,status?:string|null}  $f
     */
    public static function query(array $f): Builder
    {
        return Aset::query()
            ->with(['kategori', 'orgUnit', 'penanggungJawab'])
            ->when(! empty($f['tim']), fn ($q) => $q->tim($f['tim']))
            ->when(! empty($f['kategori_id']), fn ($q) => $q->where('kategori_inventaris_id', $f['kategori_id']))
            ->when(! empty($f['unit_id']), fn ($q) => $q->whereIn('org_unit_id', OrgUnit::denganTurunan((int) $f['unit_id'])))
            ->when(! empty($f['status']) && $f['status'] !== 'semua', fn ($q) => $q->where('status', $f['status']))
            ->orderBy('kode');
    }

    /**
     * Jumlah per status (abaikan filter status). Semua status hadir (default 0).
     *
     * @return array<string,int>
     */
    public static function hitungStatus(array $f): array
    {
        $dasar = array_fill_keys(array_map(fn ($s) => $s->value, StatusAset::cases()), 0);
        $ff = $f;
        unset($ff['status']);
        $hitung = self::query($ff)->reorder()->toBase()
            ->select('status')->selectRaw('count(*) as n')->groupBy('status')->pluck('n', 'status');
        foreach ($hitung as $s => $n) {
            $dasar[$s] = (int) $n;
        }

        return $dasar;
    }

    /** @return Collection<int,Aset> */
    public static function daftarAset(array $f): Collection
    {
        return self::query($f)->get();
    }

    /** Jumlah jadwal jatuh tempo (H-14) untuk tim tsb. */
    public static function jumlahJatuhTempo(?array $timNilai): int
    {
        return PengingatPemeliharaan::semua($timNilai)->count();
    }
}
