<?php

namespace App\Http\Controllers\Tiket;

use App\Exports\TiketExport;
use App\Http\Controllers\Controller;
use App\Support\NamaFile;
use App\Support\RekapTiket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanTiketController extends Controller
{
    /** @return list<string> */
    private function timNilai(): array
    {
        return array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
    }

    public function daftar(Request $request)
    {
        $filter = [
            'tim' => $this->timNilai(),
            'status' => $request->query('status') ?: null,
            'prioritas' => $request->query('prioritas') ?: null,
            'jenis' => $request->query('jenis') ?: null,
            'dari' => $request->query('dari') ?: null,
            'sampai' => $request->query('sampai') ?: null,
        ];

        $bagian = [];
        $tokens = [];
        if ($filter['dari'] || $filter['sampai']) {
            $bagian[] = 'Periode: '.($filter['dari'] ?: '…').' s/d '.($filter['sampai'] ?: '…');
            $tokens[] = $filter['dari'] ?: 'awal';
        }
        foreach (['status', 'prioritas', 'jenis'] as $k) {
            if ($filter[$k]) {
                $bagian[] = ucfirst($k).': '.ucfirst($filter[$k]);
                $tokens[] = $filter[$k];
            }
        }
        $keterangan = $bagian ? implode(' · ', $bagian) : 'Semua tiket';

        if ($request->query('format') === 'xlsx') {
            return Excel::download(new TiketExport($filter, $keterangan), NamaFile::laporan('daftar-tiket', $tokens, 'xlsx'));
        }

        return Pdf::loadView('laporan.pdf.tiket', [
            'tiket' => RekapTiket::query($filter)->get(),
            'metrik' => RekapTiket::metrikPerTim($filter),
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFile::laporan('daftar-tiket', $tokens, 'pdf'));
    }
}
