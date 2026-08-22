<?php

namespace App\Http\Controllers;

use App\Support\Panduan;
use Illuminate\Support\Facades\View;

class PanduanController extends Controller
{
    /** Daftar isi. Publik: bab instalasi & klaim akun justru dibaca sebelum punya akses. */
    public function index()
    {
        return view('panduan.index', ['grup' => Panduan::perGrup()]);
    }

    /** Isi satu bab. Slug tanpa view diperlakukan 404. */
    public function bab(string $bab)
    {
        $meta = Panduan::cari($bab);

        abort_if($meta === null || ! View::exists('panduan.'.$bab), 404);

        return view('panduan.'.$bab, [
            'meta' => $meta,
            'tetangga' => Panduan::tetangga($bab),
        ]);
    }
}
