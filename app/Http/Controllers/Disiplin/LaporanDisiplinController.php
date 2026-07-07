<?php

namespace App\Http\Controllers\Disiplin;

use App\Enums\TingkatSanksi;
use App\Exports\SanksiDisiplinExport;
use App\Http\Controllers\Controller;
use App\Models\OrgUnit;
use App\Support\NamaFileLaporan;
use App\Support\RekapDisiplin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanDisiplinController extends Controller
{
    public function sanksi(Request $request)
    {
        $filter = [
            'dari' => $request->query('dari'),
            'sampai' => $request->query('sampai'),
            'unit_id' => $request->query('unit_id') ?: null,
            'tingkat' => $request->query('tingkat') ?: null,
            'status' => $request->query('status') ?: null,
        ];
        $tokens = $this->token($filter);
        $keterangan = $this->keterangan($filter);

        if ($request->query('format') === 'xlsx') {
            return Excel::download(
                new SanksiDisiplinExport($filter, $keterangan),
                NamaFileLaporan::buat('rekap-sanksi-disiplin', $tokens, 'xlsx'),
            );
        }

        return Pdf::loadView('laporan.pdf.sanksi-disiplin', [
            'sanksi' => RekapDisiplin::daftarSanksi($filter),
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFileLaporan::buat('rekap-sanksi-disiplin', $tokens, 'pdf'));
    }

    /** @return array<int,string> */
    private function token(array $f): array
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
        if (! empty($f['tingkat']) && $t = TingkatSanksi::tryFrom((int) $f['tingkat'])) {
            $tokens[] = $t->label();
        }
        if (! empty($f['status'])) {
            $tokens[] = $f['status'];
        }

        return $tokens;
    }

    private function keterangan(array $f): string
    {
        $bagian = [];
        $bagian[] = 'Periode: '.($f['dari'] ?: '…').' s/d '.($f['sampai'] ?: '…');
        if (! empty($f['unit_id']) && $unit = OrgUnit::find($f['unit_id'])) {
            $bagian[] = 'Unit: '.$unit->nama.' (termasuk turunan)';
        }
        if (! empty($f['tingkat']) && $t = TingkatSanksi::tryFrom((int) $f['tingkat'])) {
            $bagian[] = 'Tingkat: '.$t->label();
        }
        $bagian[] = 'Status: '.(empty($f['status']) ? 'Semua' : ucfirst($f['status']));

        return implode(' · ', $bagian);
    }
}
