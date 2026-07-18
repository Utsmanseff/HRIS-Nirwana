<?php

namespace App\Http\Controllers\Inventaris;

use App\Exports\AsetExport;
use App\Exports\JatuhTempoPemeliharaanExport;
use App\Http\Controllers\Controller;
use App\Models\KategoriInventaris;
use App\Models\OrgUnit;
use App\Support\NamaFile;
use App\Support\PengingatPemeliharaan;
use App\Support\RekapInventaris;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanInventarisController extends Controller
{
    /** @return list<string> */
    private function timNilai(): array
    {
        return array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
    }

    public function aset(Request $request)
    {
        $filter = [
            'tim' => $this->timNilai(),
            'kategori_id' => $request->query('kategori_id') ?: null,
            'unit_id' => $request->query('unit_id') ?: null,
            'status' => $request->query('status') ?: null,
        ];

        $bagian = [];
        $tokens = [];
        if ($filter['kategori_id'] && $kat = KategoriInventaris::find($filter['kategori_id'])) {
            $bagian[] = 'Kategori: '.$kat->nama;
            $tokens[] = $kat->nama;
        }
        if ($filter['unit_id'] && $unit = OrgUnit::find($filter['unit_id'])) {
            $bagian[] = 'Unit: '.$unit->nama.' (termasuk turunan)';
            $tokens[] = $unit->nama;
        }
        $bagian[] = 'Status: '.($filter['status'] ? ucfirst((string) $filter['status']) : 'Semua');
        if ($filter['status']) {
            $tokens[] = (string) $filter['status'];
        }
        $keterangan = implode(' · ', $bagian);

        if ($request->query('format') === 'xlsx') {
            return Excel::download(new AsetExport($filter, $keterangan), NamaFile::laporan('daftar-aset', $tokens, 'xlsx'));
        }

        return Pdf::loadView('laporan.pdf.aset', [
            'aset' => RekapInventaris::daftarAset($filter),
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFile::laporan('daftar-aset', $tokens, 'pdf'));
    }

    public function pemeliharaan(Request $request)
    {
        $pengingat = PengingatPemeliharaan::semua($this->timNilai());
        $keterangan = 'Aset jatuh tempo pemeliharaan (ambang H-14)';

        if ($request->query('format') === 'xlsx') {
            return Excel::download(new JatuhTempoPemeliharaanExport($pengingat, $keterangan), NamaFile::laporan('jatuh-tempo-pemeliharaan', [], 'xlsx'));
        }

        return Pdf::loadView('laporan.pdf.jatuh-tempo-pemeliharaan', [
            'pengingat' => $pengingat,
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFile::laporan('jatuh-tempo-pemeliharaan', [], 'pdf'));
    }
}
