<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Models\OrgUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Satu sheet Excel = satu unit (baris sudah terurut nama). */
class AbsensiUnitSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use KopLaporanExcel;

    public function __construct(private OrgUnit $unit, private Collection $baris, private string $keterangan = '') {}

    public function collection(): Collection
    {
        return $this->baris;
    }

    public function title(): string
    {
        // Nama sheet Excel: maks 31 char, buang karakter terlarang \ / ? * [ ] :
        $nama = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $this->unit->nama);

        return mb_substr($nama, 0, 31) ?: 'Unit';
    }

    protected function judulLaporan(): string
    {
        return 'Laporan Absensi — '.$this->unit->nama;
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['Tanggal', 'Karyawan', 'NIP', 'Shift', 'Masuk', 'Pulang', 'Jam Kerja', 'Telat (mnt)', 'Pulang Cepat (mnt)', 'Status'];
    }

    public function map($a): array
    {
        return [
            $a->tanggal_kerja->format('Y-m-d'),
            $a->karyawan->nama_lengkap,
            $a->karyawan->nip,
            $a->shift_nama ?? '-',
            $a->jam_masuk?->format('H:i') ?? '-',
            $a->jam_pulang?->format('H:i') ?? '-',
            $a->jamKerjaLabel(),
            $a->telat_menit ?? '',
            $a->pulang_cepat_menit ?? '',
            $a->labelStatus()[0],
        ];
    }
}
