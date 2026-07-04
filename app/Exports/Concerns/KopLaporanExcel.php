<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Kop instansi + styling untuk ekspor Excel laporan.
 * Baris 1-6 = kop (rata tengah, di-merge), baris 7 kosong, baris 8 = header kolom.
 * Data mulai baris 9. Pemakai wajib menyediakan judulLaporan/keteranganLaporan/kolomLaporan.
 */
trait KopLaporanExcel
{
    abstract protected function judulLaporan(): string;

    abstract protected function keteranganLaporan(): string;

    /** @return array<int, string> label kolom (header tabel) */
    abstract protected function kolomLaporan(): array;

    private int $barisHeader = 8;

    public function headings(): array
    {
        return [
            [config('instansi.nama_resmi')],
            [config('instansi.alamat')],
            [config('instansi.telp').'  ·  '.config('instansi.email_web')],
            [$this->judulLaporan()],
            [$this->keteranganLaporan()],
            ['Dicetak: '.now()->locale('id')->translatedFormat('j F Y H:i').' oleh '.(auth()->user()->name ?? '-')],
            [],
            $this->kolomLaporan(),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $kolom = count($this->kolomLaporan());
                $lastCol = Coordinate::stringFromColumnIndex($kolom);
                $header = $this->barisHeader;
                $lastRow = max($sheet->getHighestRow(), $header);

                // Merge & center kop (baris 1-6).
                foreach (range(1, 6) as $r) {
                    $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
                }
                $sheet->getStyle("A1:{$lastCol}6")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
                $sheet->getStyle('A2:A3')->getFont()->setSize(9)->getColor()->setRGB('555555');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A5:A6')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('666666');

                // Header kolom (baris 8): tebal, latar hijau, teks putih, center.
                $styHeader = $sheet->getStyle("A{$header}:{$lastCol}{$header}");
                $styHeader->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $styHeader->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('14532D');
                $styHeader->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($header)->setRowHeight(20);

                // Border seluruh tabel (header + data).
                $sheet->getStyle("A{$header}:{$lastCol}{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CFD8D2');

                // Zebra baris data.
                for ($r = $header + 1; $r <= $lastRow; $r++) {
                    if (($r - $header) % 2 === 0) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4F7F5');
                    }
                }
            },
        ];
    }
}
