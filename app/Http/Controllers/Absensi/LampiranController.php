<?php

namespace App\Http\Controllers\Absensi;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Support\Facades\Storage;

class LampiranController extends Controller
{
    /**
     * Stream foto absensi inline. $sesi = 'masuk'|'pulang'.
     * Boleh: pemilik · HRD · koordinator unit (karyawan pemilik ada di subtree kelolaan).
     */
    public function lihat(Absensi $absensi, string $sesi)
    {
        abort_unless(in_array($sesi, ['masuk', 'pulang'], true), 404);

        $user = auth()->user();
        $pemilik = $absensi->karyawan;

        $boleh = $absensi->karyawan_id === $user->karyawan_id
            || $user->hasRole(Role::Hrd->value)
            || ($user->karyawan?->punyaBawahan()
                && $user->karyawan->karyawanKelolaan()->whereKey($pemilik->id)->exists());

        $path = $sesi === 'masuk' ? $absensi->foto_masuk_path : $absensi->foto_pulang_path;

        abort_unless($boleh && $path && Storage::disk('local')->exists($path), 403);

        return Storage::disk('local')->response($path, null, ['Content-Type' => 'image/webp']);
    }
}
