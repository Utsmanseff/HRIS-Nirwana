<?php

namespace App\Exports;

use App\Support\RekapAbsensi;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Ekspor Excel batch: satu sheet per unit (semua unit dalam periode). */
class AbsensiPerUnitExport implements WithMultipleSheets
{
    public function __construct(private array $filter, private string $keterangan = '') {}

    public function sheets(): array
    {
        return RekapAbsensi::perUnit($this->filter)
            ->map(fn (array $g) => new AbsensiUnitSheet($g['unit'], $g['baris'], $this->keterangan))
            ->all();
    }
}
