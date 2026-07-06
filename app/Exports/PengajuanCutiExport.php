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

class PengajuanCutiExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    public function __construct(private array $filter, private string $keterangan = '') {}

    public function collection(): Collection
    {
        return RekapCuti::daftarPengajuan($this->filter);
    }

    protected function judulLaporan(): string
    {
        return 'Rekap Pengajuan Cuti';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['NIP', 'Pemohon', 'Unit', 'Jenis', 'Mulai', 'Selesai', 'Hari', 'Status'];
    }

    public function map($p): array
    {
        return [
            $p->karyawan->nip,
            $p->karyawan->nama_lengkap,
            $p->karyawan->orgUnit?->nama,
            $p->jenisCuti->nama,
            $p->tanggal_mulai->format('Y-m-d'),
            $p->tanggal_selesai->format('Y-m-d'),
            $p->jumlah_hari,
            ucfirst($p->status->value),
        ];
    }
}
