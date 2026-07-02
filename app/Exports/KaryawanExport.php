<?php

namespace App\Exports;

use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KaryawanExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private array $filter) {}

    public function query(): Builder
    {
        return Karyawan::query()
            ->with(['orgUnit', 'jabatan', 'kontrakTerbaru'])
            ->saring($this->filter)
            ->orderBy('nama_lengkap');
    }

    public function headings(): array
    {
        return ['NIP', 'Nama', 'Unit', 'Jabatan', 'Kontrak Terakhir', 'Tanggal Akhir', 'Status'];
    }

    public function map($k): array
    {
        return [
            $k->nip,
            $k->nama_lengkap,
            $k->orgUnit?->nama,
            $k->jabatan?->nama,
            $k->kontrakTerbaru?->jenis->label(),
            $k->kontrakTerbaru?->tanggal_akhir?->format('Y-m-d'),
            $k->status->value,
        ];
    }
}
