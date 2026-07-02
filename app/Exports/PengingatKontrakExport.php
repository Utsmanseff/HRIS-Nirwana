<?php

namespace App\Exports;

use App\Support\PengingatKontrak;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PengingatKontrakExport implements FromCollection, WithHeadings
{
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

    public function headings(): array
    {
        return ['NIP', 'Nama', 'Unit', 'Tahap', 'Tanggal Akhir', 'Sisa Hari', 'Status'];
    }
}
