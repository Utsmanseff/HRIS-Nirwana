<?php

namespace App\Support;

use App\Models\Absensi;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RekapAbsensi
{
    /**
     * @param  array{dari?:string|null,sampai?:string|null,unit?:int|null,cari?:string|null,status?:string|null}  $f
     */
    public static function query(array $f): Builder
    {
        return Absensi::query()
            ->with(['karyawan.jabatan', 'karyawan.orgUnit', 'shift'])
            ->when(! empty($f['dari']), fn ($q) => $q->whereDate('tanggal_kerja', '>=', $f['dari']))
            ->when(! empty($f['sampai']), fn ($q) => $q->whereDate('tanggal_kerja', '<=', $f['sampai']))
            ->when(! empty($f['unit']), function ($q) use ($f) {
                $ids = OrgUnit::denganTurunan((int) $f['unit']);
                $q->whereHas('karyawan', fn ($k) => $k->whereIn('org_unit_id', $ids));
            })
            ->when(! empty($f['cari']), function ($q) use ($f) {
                $c = $f['cari'];
                $q->whereHas('karyawan', fn ($k) => $k->where('nama_lengkap', 'like', "%{$c}%")->orWhere('nip', 'like', "%{$c}%"));
            })
            ->orderByDesc('tanggal_kerja')
            ->orderByDesc('jam_masuk');
    }

    /**
     * Ambil baris + filter status derived (di PHP karena bergantung now()/durasi).
     *
     * @param  array<string,mixed>  $f
     */
    public static function ambil(array $f): Collection
    {
        $rows = self::query($f)->get();
        if (! empty($f['status'])) {
            $rows = $rows->filter(fn (Absensi $a) => $a->statusRekap() === $f['status'])->values();
        }

        return $rows;
    }

    /**
     * Kelompokkan baris per unit (semua unit; abaikan filter unit), tiap kelompok terurut nama karyawan.
     * Kelompok terurut nama unit.
     *
     * @param  array<string,mixed>  $f
     * @return Collection<int,array{unit:\App\Models\OrgUnit,baris:Collection}>
     */
    public static function perUnit(array $f): Collection
    {
        // Hormati filter unit (lingkup): null = semua unit, else subtree unit tsb.
        $rows = self::ambil($f);

        return $rows->groupBy(fn (Absensi $a) => $a->karyawan->org_unit_id)
            ->map(fn (Collection $grup) => [
                'unit' => $grup->first()->karyawan->orgUnit,
                'baris' => $grup->sortBy(fn (Absensi $a) => $a->karyawan->nama_lengkap)->values(),
            ])
            ->sortBy(fn (array $g) => $g['unit']?->nama)
            ->values();
    }

    /**
     * Statistik ringkas untuk stat cards.
     *
     * @param  array<string,mixed>  $f
     * @return array{hadir:int,telat:int,pulang_cepat:int,anomali:int}
     */
    public static function statistik(array $f): array
    {
        $rows = self::query($f)->get();

        return [
            'hadir' => $rows->count(),
            'telat' => $rows->filter(fn (Absensi $a) => $a->statusRekap() === 'telat')->count(),
            'pulang_cepat' => $rows->filter(fn (Absensi $a) => $a->statusRekap() === 'pulang_cepat')->count(),
            'anomali' => $rows->filter(fn (Absensi $a) => $a->statusRekap() === 'anomali')->count(),
        ];
    }
}
