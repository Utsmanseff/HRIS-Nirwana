<?php

namespace App\Http\Controllers\Inventaris;

use App\Http\Controllers\Controller;
use App\Models\LampiranAset;
use Illuminate\Support\Facades\Storage;

class LampiranAsetController extends Controller
{
    public function lihat(LampiranAset $lampiran)
    {
        $lampiran->load('aset.kategori');
        $timNilai = array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
        abort_unless(in_array($lampiran->aset->kategori->tim->value, $timNilai, true), 403);
        abort_unless(Storage::disk('local')->exists($lampiran->path), 404);

        return Storage::disk('local')->response($lampiran->path);
    }
}
