<?php

namespace App\Http\Controllers\Cuti;

use App\Exports\PengajuanCutiExport;
use App\Exports\SaldoCutiExport;
use App\Http\Controllers\Controller;
use App\Models\JenisCuti;
use App\Models\OrgUnit;
use App\Support\NamaFileLaporan;
use App\Support\RekapCuti;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanCutiController extends Controller
{
    public function pengajuan(Request $request)
    {
        $filter = [
            'dari' => $request->query('dari'),
            'sampai' => $request->query('sampai'),
            'unit_id' => $request->query('unit_id') ?: null,
            'jenis_id' => $request->query('jenis_id') ?: null,
            'status' => $request->query('status') ?: null,
        ];
        $tokens = $this->tokenPengajuan($filter);
        $keterangan = $this->keteranganPengajuan($filter);

        if ($request->query('format') === 'xlsx') {
            return Excel::download(
                new PengajuanCutiExport($filter, $keterangan),
                NamaFileLaporan::buat('rekap-pengajuan-cuti', $tokens, 'xlsx'),
            );
        }

        return Pdf::loadView('laporan.pdf.pengajuan-cuti', [
            'pengajuan' => RekapCuti::daftarPengajuan($filter),
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFileLaporan::buat('rekap-pengajuan-cuti', $tokens, 'pdf'));
    }

    public function saldo(Request $request)
    {
        $unitId = $request->query('unit_id') ? (int) $request->query('unit_id') : null;
        $tokens = [];
        $keterangan = 'Semua unit';
        if ($unitId && $unit = OrgUnit::find($unitId)) {
            $tokens[] = $unit->nama;
            $keterangan = 'Unit: '.$unit->nama.' (termasuk turunan)';
        }

        if ($request->query('format') === 'xlsx') {
            return Excel::download(
                new SaldoCutiExport($unitId, $keterangan),
                NamaFileLaporan::buat('saldo-cuti', $tokens, 'xlsx'),
            );
        }

        return Pdf::loadView('laporan.pdf.saldo-cuti', [
            'saldo' => RekapCuti::saldoKaryawan($unitId),
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFileLaporan::buat('saldo-cuti', $tokens, 'pdf'));
    }

    /** @return array<int,string> */
    private function tokenPengajuan(array $f): array
    {
        $tokens = [];
        if (! empty($f['dari'])) {
            $tokens[] = $f['dari'];
        }
        if (! empty($f['sampai'])) {
            $tokens[] = $f['sampai'];
        }
        if (! empty($f['unit_id']) && $unit = OrgUnit::find($f['unit_id'])) {
            $tokens[] = $unit->nama;
        }
        if (! empty($f['jenis_id']) && $jenis = JenisCuti::find($f['jenis_id'])) {
            $tokens[] = $jenis->nama;
        }
        if (! empty($f['status'])) {
            $tokens[] = $f['status'];
        }

        return $tokens;
    }

    private function keteranganPengajuan(array $f): string
    {
        $bagian = [];
        $bagian[] = 'Periode: '.($f['dari'] ?: '…').' s/d '.($f['sampai'] ?: '…');
        if (! empty($f['unit_id']) && $unit = OrgUnit::find($f['unit_id'])) {
            $bagian[] = 'Unit: '.$unit->nama.' (termasuk turunan)';
        }
        if (! empty($f['jenis_id']) && $jenis = JenisCuti::find($f['jenis_id'])) {
            $bagian[] = 'Jenis: '.$jenis->nama;
        }
        $bagian[] = 'Status: '.(empty($f['status']) ? 'Semua' : ucfirst($f['status']));

        return implode(' · ', $bagian);
    }
}
