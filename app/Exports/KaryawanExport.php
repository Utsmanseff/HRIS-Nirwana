<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KaryawanExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    public function __construct(private array $filter, private string $keterangan = '') {}

    public function query(): Builder
    {
        return Karyawan::query()
            ->with(['orgUnit', 'jabatan', 'kontrakTerbaru'])
            ->saring($this->filter)
            ->orderBy('nama_lengkap');
    }

    protected function judulLaporan(): string
    {
        return 'Daftar Karyawan';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
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
            ucfirst($k->status->value),
        ];
    }
}
