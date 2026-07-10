<?php

namespace App\Http\Controllers\Absensi;

use App\Enums\Role;
use App\Exports\AbsensiExport;
use App\Http\Controllers\Controller;
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
            'unit' => $request->query('unit') ?: null,
            'status' => $request->query('status') ?: null,
            'cari' => $request->query('cari') ?: null,
        ];

        // Koordinator non-HRD dibatasi subtree (guard server, selaras Livewire).
        $user = auth()->user();
        if (! $user->hasRole(Role::Hrd->value) && ! $filter['unit']) {
            $unitDipimpin = $user->karyawan?->unitDipimpin();
            if ($unitDipimpin && $unitDipimpin->isNotEmpty()) {
                $filter['unit'] = $unitDipimpin->first()->id;
            }
        }

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

        if ($request->query('format') === 'xlsx') {
            return Excel::download(new AbsensiExport($filter, $keterangan), NamaFileLaporan::buat('laporan-absensi', $tokens, 'xlsx'));
        }

        return Pdf::loadView('laporan.pdf.absensi', [
            'baris' => RekapAbsensi::ambil($filter),
            'keteranganFilter' => $keterangan,
        ])->setPaper('a4', 'landscape')->download(NamaFileLaporan::buat('laporan-absensi', $tokens, 'pdf'));
    }
}
