<?php

namespace App\Http\Controllers\Tiket;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\LampiranTiket;
use Illuminate\Support\Facades\Storage;

class LampiranTiketController extends Controller
{
    public function lihat(LampiranTiket $lampiran)
    {
        $user = auth()->user();
        $tiket = $lampiran->tiket;
        $timUser = array_map(fn ($t) => $t->value, $user->timTeknis());

        $boleh = $user->hasRole(Role::AdminSistem->value)
            || in_array($tiket->tim->value, $timUser, true)
            || ($tiket->pelapor_id && $tiket->pelapor_id === $user->karyawan_id);

        abort_unless($boleh, 403);

        return Storage::disk('local')->response($lampiran->path);
    }
}
