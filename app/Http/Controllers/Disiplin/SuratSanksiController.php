<?php

namespace App\Http\Controllers\Disiplin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\SanksiDisiplin;
use App\Support\NamaFile;
use Illuminate\Support\Facades\Storage;

class SuratSanksiController extends Controller
{
    /** Stream surat sanksi inline. Boleh: karyawan-ybs, pengusul, approver di rantai, atau HRD. */
    public function lihat(SanksiDisiplin $sanksi)
    {
        $user = auth()->user();
        $karId = $user->karyawan_id;
        $bolehApprover = $sanksi->approval()->where('approver_id', $karId)->exists();
        $boleh = $sanksi->karyawan_id === $karId
            || $sanksi->pengusul_id === $karId
            || $bolehApprover
            || $user->hasRole(Role::Hrd->value);

        abort_unless($boleh && $sanksi->surat_path, 403);

        return Storage::disk('local')->response($sanksi->surat_path, self::namaBerkas($sanksi));
    }

    /** Nama baku: surat-{peringatan|teguran}_{tingkat}_{nama}_{tanggal terbit}.pdf */
    private static function namaBerkas(SanksiDisiplin $sanksi): string
    {
        $jenis = $sanksi->tingkat->jenis() === 'sp' ? 'surat-peringatan' : 'surat-teguran';

        return NamaFile::surat(
            $jenis,
            [$sanksi->tingkat->label(), $sanksi->karyawan->nama_lengkap],
            $sanksi->tanggal_terbit,
            'pdf',
        );
    }
}
