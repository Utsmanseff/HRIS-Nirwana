<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Support\RekapDisiplin;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SanksiDisiplinExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    public function __construct(private array $filter, private string $keterangan = '') {}

    public function collection(): Collection
    {
        return RekapDisiplin::daftarSanksi($this->filter);
    }

    protected function judulLaporan(): string
    {
        return 'Rekap Sanksi Disiplin';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['NIP', 'Karyawan', 'Unit', 'Tingkat', 'Tgl Kejadian', 'Pengusul', 'Nomor Surat', 'Status'];
    }

    public function map($s): array
    {
        return [
            $s->karyawan->nip,
            $s->karyawan->nama_lengkap,
            $s->karyawan->orgUnit?->nama,
            $s->tingkat->label(),
            $s->tanggal_kejadian->format('Y-m-d'),
            $s->pengusul->nama_lengkap,
            $s->nomor_surat,
            $s->status->label(),
        ];
    }
}
