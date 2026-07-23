<?php

namespace App\Http\Controllers;

use App\Enums\PeranApproval;
use App\Enums\StatusPengajuanCuti;
use App\Models\PengajuanCuti;
use Illuminate\Support\Carbon;

class VerifikasiCutiController extends Controller
{
    /** Halaman publik verifikasi. Signature sudah divalidasi middleware 'signed'. */
    public function tampil(PengajuanCuti $pengajuan, string $sumber)
    {
        $pengajuan->load(['karyawan.jabatan', 'approval.approver']);

        $penanda = $this->resolve($pengajuan, $sumber);
        if (! $penanda) {
            return response()->view('verifikasi.cuti', ['invalid' => true], 404);
        }

        $tgl = fn (?Carbon $d) => $d ? $d->locale('id')->translatedFormat('j F Y') : '-';

        return view('verifikasi.cuti', [
            'invalid' => false,
            'status' => $this->status($pengajuan, $tgl),
            'tanggalMulai' => $tgl($pengajuan->tanggal_mulai),
            'tanggalSelesai' => $tgl($pengajuan->tanggal_selesai),
            'jumlahHari' => $pengajuan->jumlah_hari,
            'karyawan' => $pengajuan->karyawan->nama_lengkap,
            'penandaNama' => $penanda['nama'],
            'penandaPeran' => $penanda['peran'],
            'penandaTanggal' => $tgl($penanda['tanggal']),
        ]);
    }

    /** @return array{nama:string,peran:string,tanggal:?Carbon}|null */
    private function resolve(PengajuanCuti $pengajuan, string $sumber): ?array
    {
        if ($sumber === 'pemohon') {
            $k = $pengajuan->karyawan;
            if (! $k) {
                return null;
            }

            return ['nama' => $k->nama_lengkap, 'peran' => 'Pemohon', 'tanggal' => $pengajuan->created_at];
        }

        $peranEnum = match ($sumber) {
            'koordinator' => PeranApproval::Koordinator,
            'kabid' => PeranApproval::Kabid,
            'hrd' => PeranApproval::Hrd,
            'direktur' => PeranApproval::Direktur,
            default => null,
        };

        if (! $peranEnum) {
            return null;
        }

        $step = $pengajuan->approval->firstWhere('peran', $peranEnum);
        if (! $step || ! $step->approver) {
            return null;
        }

        return [
            'nama' => $step->approver->nama_lengkap,
            'peran' => $peranEnum->label(),
            'tanggal' => $step->acted_at,
        ];
    }

    /** @return array{label:string,varian:string,ket:string} */
    private function status(PengajuanCuti $pengajuan, callable $tgl): array
    {
        return match ($pengajuan->status) {
            StatusPengajuanCuti::Disetujui => ['label' => 'DISETUJUI', 'varian' => 'ok', 'ket' => ''],
            StatusPengajuanCuti::Dibatalkan => ['label' => 'DIBATALKAN', 'varian' => 'danger', 'ket' => $pengajuan->alasan_batal ?? ''],
            StatusPengajuanCuti::Ditolak => ['label' => 'DITOLAK', 'varian' => 'danger', 'ket' => ''],
            StatusPengajuanCuti::Diproses => ['label' => 'DIPROSES', 'varian' => 'muted', 'ket' => ''],
            default => ['label' => strtoupper($pengajuan->status->value), 'varian' => 'muted', 'ket' => ''],
        };
    }
}
