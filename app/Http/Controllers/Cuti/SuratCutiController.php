<?php

namespace App\Http\Controllers\Cuti;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\PengajuanCuti;
use App\Support\NamaFile;
use Illuminate\Support\Facades\Storage;

class SuratCutiController extends Controller
{
    /** Stream surat keterangan cuti. Boleh: pemohon, approver di rantai, atau HRD. */
    public function lihat(PengajuanCuti $pengajuan)
    {
        $user = auth()->user();
        $karId = $user->karyawan_id;
        $bolehApprover = $pengajuan->approval()->where('approver_id', $karId)->exists();
        $boleh = $pengajuan->karyawan_id === $karId
            || $bolehApprover
            || $user->hasRole(Role::Hrd->value);

        abort_unless($boleh && $pengajuan->surat_path, 403);

        return Storage::disk('local')->response($pengajuan->surat_path, self::namaBerkas($pengajuan));
    }

    /** Tanggal surat = tahap approval terakhir yang bertindak (saat surat lahir). Null → hari ini. */
    private static function namaBerkas(PengajuanCuti $pengajuan): string
    {
        $tanggal = $pengajuan->approval()
            ->whereNotNull('acted_at')
            ->orderByDesc('acted_at')
            ->first()?->acted_at;

        return NamaFile::surat('surat-keterangan-cuti', [$pengajuan->karyawan->nama_lengkap], $tanggal, 'pdf');
    }
}
