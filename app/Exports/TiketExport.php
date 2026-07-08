<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Support\RekapTiket;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TiketExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    public function __construct(private array $filter, private string $keterangan = '') {}

    public function collection(): Collection
    {
        return RekapTiket::query($this->filter)->reorder()->orderByDesc('waktu_lapor')->get();
    }

    protected function judulLaporan(): string
    {
        return 'Daftar Tiket';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['Nomor', 'Judul', 'Jenis', 'Tim', 'Pelapor', 'Prioritas', 'Status', 'Lapor', 'Respon (mnt)', 'Selesai (mnt)'];
    }

    public function map($t): array
    {
        return [
            $t->nomor,
            $t->judul,
            $t->jenis->label(),
            $t->tim->label(),
            $t->pelapor?->nama_lengkap ?? 'Internal',
            $t->prioritas->label(),
            $t->status->label(),
            $t->waktu_lapor->format('Y-m-d H:i'),
            $t->menitRespon() ?? '',
            $t->menitPenyelesaian() ?? '',
        ];
    }
}
