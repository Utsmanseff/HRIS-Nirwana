<?php

namespace App\Http\Controllers\Absensi;

use App\Exports\AbsensiExport;
use App\Exports\AbsensiPerUnitExport;
use App\Http\Controllers\Controller;
use App\Support\LingkupAbsensi;
use App\Support\NamaFileLaporan;
use App\Support\RekapAbsensi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanAbsensiController extends Controller
{
    public function unduh(Request $request)
    {
        $filter = [
            'dari' => $request->query('dari') ?: null,
            'sampai' => $request->query('sampai') ?: null,
            'unit' => LingkupAbsensi::unitEfektif(auth()->user(), $request->query('unit') ?: null),
            'status' => $request->query('status') ?: null,
            'cari' => $request->query('cari') ?: null,
        ];

        $bagian = [];
        $tokens = [];
        if ($filter['dari'] || $filter['sampai']) {
            $bagian[] = 'Periode: '.($filter['dari'] ?: '…').' s/d '.($filter['sampai'] ?: '…');
            $tokens[] = $filter['dari'] ?: 'awal';
        }
        if ($filter['status']) {
            $bagian[] = 'Status: '.ucfirst(str_replace('_', ' ', $filter['status']));
            $tokens[] = $filter['status'];
        }
        $keterangan = $bagian ? implode(' · ', $bagian) : 'Semua absensi';

        // Mode batch per-unit: tiap unit terpisah (sheet Excel / halaman PDF).
        // Privileged = semua unit; koordinator = subtree yang dipimpin.
        if ($request->query('mode') === 'per-unit') {
            $filter['unit'] = LingkupAbsensi::bisaSemua(auth()->user()) ? null : $filter['unit'];
            $tokens[] = 'per-unit';

            if ($request->query('format') === 'xlsx') {
                return Excel::download(new AbsensiPerUnitExport($filter, $keterangan), NamaFileLaporan::buat('laporan-absensi', $tokens, 'xlsx'));
            }

            return Pdf::loadView('laporan.pdf.absensi-per-unit', [
                'grup' => RekapAbsensi::perUnit($filter),
                'keteranganFilter' => $keterangan,
            ])->setPaper('a4', 'landscape')->download(NamaFileLaporan::buat('laporan-absensi', $tokens, 'pdf'));
        }

        if ($request->query('format') === 'xlsx') {
            return Excel::download(new AbsensiExport($filter, $keterangan), NamaFileLaporan::buat('laporan-absensi', $tokens, 'xlsx'));
        }

        return Pdf::loadView('laporan.pdf.absensi', [
            'baris' => RekapAbsensi::ambil($filter),
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFileLaporan::buat('laporan-absensi', $tokens, 'pdf'));
    }
}
