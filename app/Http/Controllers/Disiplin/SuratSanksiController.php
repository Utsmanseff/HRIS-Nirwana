<?php

namespace App\Http\Controllers\Disiplin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\SanksiDisiplin;
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

        return Storage::disk('local')->response($sanksi->surat_path);
    }
}
