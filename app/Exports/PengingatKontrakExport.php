<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Support\PengingatKontrak;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PengingatKontrakExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings
{
    use KopLaporanExcel;

    public function collection(): Collection
    {
        return PengingatKontrak::semua()
            ->sortBy('sisaHari')->values()
            ->map(fn ($p) => [
                $p->karyawan->nip,
                $p->karyawan->nama_lengkap,
                $p->karyawan->orgUnit?->nama,
                $p->kontrak->jenis->label(),
                $p->kontrak->tanggal_akhir->format('Y-m-d'),
                $p->sisaHari,
                $p->sisaHari < 0 ? 'Terlewat' : 'Akan berakhir',
            ]);
    }

    protected function judulLaporan(): string
    {
        return 'Pengingat Kontrak (Akan Berakhir / Terlewat)';
    }

    protected function keteranganLaporan(): string
    {
        return 'Seluruh karyawan aktif · derived dari kontrak terakhir per karyawan';
    }

    protected function kolomLaporan(): array
    {
        return ['NIP', 'Nama', 'Unit', 'Tahap', 'Tanggal Akhir', 'Sisa Hari', 'Status'];
    }
}
