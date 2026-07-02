<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Dokumen;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenController extends Controller
{
    public function unduh(Dokumen $dokumen): StreamedResponse
    {
        return Storage::disk('local')->download($dokumen->path);
    }
}
