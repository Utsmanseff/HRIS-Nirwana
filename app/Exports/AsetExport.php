<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Support\RekapInventaris;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AsetExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    public function __construct(private array $filter, private string $keterangan = '') {}

    public function collection(): Collection
    {
        return RekapInventaris::daftarAset($this->filter);
    }

    protected function judulLaporan(): string
    {
        return 'Daftar Aset';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['Kode', 'Nama', 'Kategori', 'Tim', 'Lokasi', 'Status', 'Penanggung Jawab'];
    }

    public function map($a): array
    {
        return [
            $a->kode,
            $a->nama,
            $a->kategori?->nama,
            $a->kategori?->tim?->label(),
            $a->orgUnit?->nama,
            $a->status->label(),
            $a->penanggungJawab?->nama_lengkap,
        ];
    }
}
