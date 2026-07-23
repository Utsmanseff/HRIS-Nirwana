<?php

namespace App\Exports;

use App\Exports\Concerns\KopLaporanExcel;
use App\Support\LabelPengganti;
use App\Support\RekapAbsensi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AbsensiExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use KopLaporanExcel;

    public function __construct(private array $filter, private string $keterangan = '') {}

    private ?Collection $baris = null;

    /** @var array<int,string> absensi_id => label pengganti */
    private array $labelPengganti = [];

    public function collection(): Collection
    {
        if ($this->baris === null) {
            $this->baris = RekapAbsensi::ambil($this->filter);
            $this->labelPengganti = LabelPengganti::petaAbsensi($this->baris);
        }

        return $this->baris;
    }

    protected function judulLaporan(): string
    {
        return 'Laporan Absensi';
    }

    protected function keteranganLaporan(): string
    {
        return $this->keterangan;
    }

    protected function kolomLaporan(): array
    {
        return ['Tanggal', 'Karyawan', 'NIP', 'Shift', 'Masuk', 'Pulang', 'Jam Kerja', 'Telat (mnt)', 'Pulang Cepat (mnt)', 'Status', 'Keterangan'];
    }

    public function map($a): array
    {
        $this->collection();   // pastikan peta label terisi bila map dipanggil duluan

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
            $this->labelPengganti[$a->id] ?? '',
        ];
    }
}
