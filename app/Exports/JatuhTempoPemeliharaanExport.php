<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JatuhTempoPemeliharaanExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    /** @param Collection<int,\App\Support\PengingatPemeliharaan> $pengingat */
    public function __construct(private Collection $pengingat, private string $keterangan = '') {}

    public function collection(): Collection
    {
        return $this->pengingat;
    }

    protected function judulLaporan(): string
    {
        return 'Aset Jatuh Tempo Pemeliharaan';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['Kode', 'Aset', 'Kategori', 'Jadwal', 'Terakhir', 'Berikutnya', 'Sisa Hari'];
    }

    public function map($p): array
    {
        $aset = $p->jadwal->aset;

        return [
            $aset->kode,
            $aset->nama,
            $aset->kategori?->nama,
            $p->jadwal->nama,
            $p->jadwal->terakhir_dilakukan?->format('Y-m-d') ?? 'belum pernah',
            $p->jadwal->berikutnya()?->format('Y-m-d') ?? '—',
            $p->sisaHari,
        ];
    }
}
