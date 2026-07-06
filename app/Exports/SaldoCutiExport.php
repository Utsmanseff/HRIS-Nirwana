<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Support\RekapCuti;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SaldoCutiExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    public function __construct(private ?int $unitId, private string $keterangan = '') {}

    public function collection(): Collection
    {
        return RekapCuti::saldoKaryawan($this->unitId);
    }

    protected function judulLaporan(): string
    {
        return 'Saldo Cuti per Karyawan';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['NIP', 'Nama', 'Unit', 'Jatah', 'Terpakai', 'Sisa'];
    }

    public function map($r): array
    {
        return [
            $r['nip'],
            $r['nama'],
            $r['unit'],
            $r['jatah'],
            $r['terpakai'],
            $r['sisa'],
        ];
    }
}
