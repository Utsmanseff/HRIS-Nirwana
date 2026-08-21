<?php

namespace App\Http\Controllers\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Support\LingkupAbsensi;
use Illuminate\Support\Facades\Storage;

class LampiranController extends Controller
{
    /**
     * Stream foto absensi inline. $sesi = 'masuk'|'pulang'.
     * Boleh: pemilik · siapa pun yang lingkup laporan absensinya mencakup pemilik
     * (HRD/Staff HR/Admin Sistem = semua unit; pemimpin unit = subtree-nya).
     *
     * Dipakai bersama oleh riwayat karyawan DAN laporan absensi, jadi izinnya sengaja
     * diturunkan dari LingkupAbsensi yang sama dengan query laporan — kalau tidak,
     * Staff HR/Admin Sistem bisa membuka laporan tapi semua fotonya 403.
     */
    public function lihat(Absensi $absensi, string $sesi)
    {
        abort_unless(in_array($sesi, ['masuk', 'pulang'], true), 404);

        $user = auth()->user();
        $pemilik = $absensi->karyawan;

        $boleh = $absensi->karyawan_id === $user->karyawan_id
            || LingkupAbsensi::bisaSemua($user)
            || in_array($pemilik->org_unit_id, LingkupAbsensi::subtreeIds($user->karyawan), true);

        $path = $sesi === 'masuk' ? $absensi->foto_masuk_path : $absensi->foto_pulang_path;

        abort_unless($boleh && $path && Storage::disk('local')->exists($path), 403);

        return Storage::disk('local')->response($path, null, ['Content-Type' => 'image/webp']);
    }
}
