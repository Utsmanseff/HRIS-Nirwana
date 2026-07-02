<?php

namespace App\Http\Controllers\Sdm;

use App\Exports\KaryawanExport;
use App\Exports\PengingatKontrakExport;
use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Support\PengingatKontrak;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanSdmController extends Controller
{
    public function karyawan(Request $request)
    {
        $filter = $request->only(['cari', 'unit_id', 'level', 'kontrak_jenis', 'status']);

        if ($request->query('format') === 'xlsx') {
            return Excel::download(new KaryawanExport($filter), 'daftar-karyawan.xlsx');
        }

        $karyawan = Karyawan::query()
            ->with(['orgUnit', 'jabatan', 'kontrakTerbaru'])
            ->saring($filter)
            ->orderBy('nama_lengkap')
            ->get();

        return Pdf::loadView('laporan.pdf.karyawan', [
            'karyawan' => $karyawan,
            'keteranganFilter' => $this->keteranganFilter($filter),
        ])->setPaper('a4', 'landscape')->download('daftar-karyawan.pdf');
    }

    public function pengingatKontrak(Request $request)
    {
        if ($request->query('format') === 'xlsx') {
            return Excel::download(new PengingatKontrakExport, 'pengingat-kontrak.xlsx');
        }

        return Pdf::loadView('laporan.pdf.pengingat-kontrak', [
            'pengingat' => PengingatKontrak::semua()->sortBy('sisaHari')->values(),
        ])->setPaper('a4', 'landscape')->download('pengingat-kontrak.pdf');
    }

    /** Rangkai keterangan filter utk kop (spec: pembaca wajib tahu cakupan data). */
    private function keteranganFilter(array $f): string
    {
        $bagian = [];
        if (! empty($f['cari'])) {
            $bagian[] = 'Cari: "'.$f['cari'].'"';
        }
        if (! empty($f['unit_id']) && $unit = OrgUnit::find($f['unit_id'])) {
            $bagian[] = 'Unit: '.$unit->nama.' (termasuk turunan)';
        }
        if (! empty($f['level'])) {
            $bagian[] = 'Level: L'.$f['level'];
        }
        if (! empty($f['kontrak_jenis'])) {
            $bagian[] = 'Kontrak: '.$f['kontrak_jenis'];
        }
        $status = $f['status'] ?? '';
        $bagian[] = 'Status: '.($status === '' || $status === 'semua' ? 'Semua' : ucfirst($status));

        return implode(' · ', $bagian);
    }
}
