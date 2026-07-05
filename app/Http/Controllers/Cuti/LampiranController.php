<?php

namespace App\Http\Controllers\Cuti;

use App\Http\Controllers\Controller;
use App\Models\PengajuanCuti;
use Illuminate\Support\Facades\Storage;

class LampiranController extends Controller
{
    /** Stream lampiran inline. Boleh: pemohon, approver di rantai, atau HRD. */
    public function lihat(PengajuanCuti $pengajuan)
    {
        $user = auth()->user();
        $karId = $user->karyawan_id;
        $bolehApprover = $pengajuan->approval()->where('approver_id', $karId)->exists();
        $boleh = $pengajuan->karyawan_id === $karId
            || $bolehApprover
            || $user->hasRole(\App\Enums\Role::Hrd->value);

        abort_unless($boleh && $pengajuan->lampiran_path, 403);

        return Storage::disk('local')->response($pengajuan->lampiran_path);
    }
}
