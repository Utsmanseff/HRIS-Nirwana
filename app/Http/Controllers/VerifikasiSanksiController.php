<?php

namespace App\Http\Controllers;

use App\Enums\JabatanLevel;
use App\Enums\PeranApproval;
use App\Enums\StatusSanksi;
use App\Models\SanksiDisiplin;
use Illuminate\Support\Carbon;

class VerifikasiSanksiController extends Controller
{
    /** Halaman publik verifikasi. Signature sudah divalidasi middleware 'signed'. */
    public function tampil(SanksiDisiplin $sanksi, string $sumber)
    {
        $sanksi->load(['karyawan.orgUnit', 'pengusul.jabatan', 'approval.approver']);

        $penanda = $this->resolve($sanksi, $sumber);
        if (! $penanda) {
            return response()->view('verifikasi.sanksi', ['invalid' => true], 404);
        }

        $tgl = fn (?Carbon $d) => $d ? $d->locale('id')->translatedFormat('j F Y') : '-';

        return view('verifikasi.sanksi', [
            'invalid' => false,
            'status' => $this->status($sanksi, $tgl),
            'nomor' => $sanksi->nomor_surat ?? '-',
            'perihal' => 'Surat '.($sanksi->tingkat->jenis() === 'sp' ? 'Peringatan' : 'Teguran').' — '.$sanksi->tingkat->label(),
            'karyawan' => $sanksi->karyawan->nama_lengkap,
            'unit' => $sanksi->karyawan->orgUnit?->nama ?? '-',
            'penandaNama' => $penanda['nama'],
            'penandaPeran' => $penanda['peran'],
            'penandaTanggal' => $tgl($penanda['tanggal']),
        ]);
    }

    /** @return array{nama:string,peran:string,tanggal:?Carbon}|null */
    private function resolve(SanksiDisiplin $sanksi, string $sumber): ?array
    {
        if ($sumber === 'penerbit') {
            $step = $sanksi->approval->firstWhere('peran', PeranApproval::Direktur);

            return $step && $step->approver ? [
                'nama' => $step->approver->nama_lengkap,
                'peran' => 'Direktur',
                'tanggal' => $step->acted_at ?? $sanksi->tanggal_terbit,
            ] : null;
        }

        if ($sumber === 'kabid') {
            $step = $sanksi->approval->firstWhere('peran', PeranApproval::Kabid);

            return $step && $step->approver ? [
                'nama' => $step->approver->nama_lengkap,
                'peran' => 'Kabid',
                'tanggal' => $step->acted_at,
            ] : null;
        }

        // sumber === 'pengusul'
        $p = $sanksi->pengusul;
        if (! $p) {
            return null;
        }
        $peran = match ($p->jabatan?->level?->value) {
            JabatanLevel::Koordinator->value => 'Koordinator',
            JabatanLevel::Kabid->value => 'Kabid',
            JabatanLevel::Direktur->value => 'Direktur',
            default => $p->jabatan?->nama ?? 'Pengusul',
        };

        return ['nama' => $p->nama_lengkap, 'peran' => $peran, 'tanggal' => $sanksi->created_at];
    }

    /** @return array{label:string,varian:string,ket:string} */
    private function status(SanksiDisiplin $sanksi, callable $tgl): array
    {
        return match ($sanksi->status) {
            StatusSanksi::Diterbitkan => ['label' => 'BERLAKU', 'varian' => 'ok', 'ket' => 'Berlaku sampai '.$tgl($sanksi->berlaku_sampai)],
            StatusSanksi::Dicabut => ['label' => 'DICABUT', 'varian' => 'danger', 'ket' => 'Dicabut '.$tgl($sanksi->dicabut_pada).($sanksi->alasan_cabut ? ' — '.$sanksi->alasan_cabut : '')],
            default => ['label' => strtoupper($sanksi->status->value), 'varian' => 'muted', 'ket' => ''],
        };
    }
}
