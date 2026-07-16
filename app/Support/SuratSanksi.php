<?php

namespace App\Support;

use App\Enums\JabatanLevel;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\TandaTanganQR;

class SuratSanksi
{
    /** Generate surat sanksi PDF, simpan ke disk local privat, kembalikan path relatif. */
    public static function generate(SanksiDisiplin $sanksi): string
    {
        $sanksi->loadMissing(['karyawan.jabatan', 'karyawan.orgUnit', 'pengusul.jabatan', 'approval.approver.jabatan']);

        $pdf = Pdf::loadView('surat.sanksi', [
            'sanksi' => $sanksi,
            'ttd' => self::penandatangan($sanksi),
        ])->setPaper('a4', 'portrait')->output();

        $path = "sanksi/{$sanksi->id}/surat-".Str::slug($sanksi->nomor_surat ?? 'sanksi').'-'.Str::random(6).'.pdf';
        Storage::disk('local')->put($path, $pdf);

        return $path;
    }

    /**
     * Data tanda tangan surat 2-halaman.
     * - penerbit: ttd hal-1 (Direktur, tahap final).
     * - pengusulChain: ttd hal-2 (pengusul + approver Koordinator/Kabid), hanya bila pengusul unit.
     *
     * @return array{penerbit: ?array, pengusulChain: list<array>, pakaiHal2: bool}
     */
    public static function penandatangan(SanksiDisiplin $sanksi): array
    {
        $sanksi->loadMissing(['pengusul.jabatan', 'pengusul.user', 'approval.approver.jabatan']);

        $entri = fn (Karyawan $k, string $peran, $tgl, ?string $sumber): array => [
            'nama' => $k->nama_lengkap,
            'jabatan' => $k->jabatan?->nama,
            'peran' => $peran,
            'tanggal' => $tgl,
            'sumber' => $sumber,
            'qr' => $sumber ? TandaTanganQR::qr(TandaTanganQR::url($sanksi, $sumber)) : null,
        ];

        // Penerbit (hal-1) = approver peran Direktur.
        $direkturStep = $sanksi->approval->firstWhere('peran', PeranApproval::Direktur);
        $penerbit = $direkturStep
            ? $entri($direkturStep->approver, 'Direktur', $direkturStep->acted_at ?? $sanksi->tanggal_terbit, 'penerbit')
            : null;

        // Hal-2 hanya bila pengusul = Koordinator/Kabid (unit), bukan HRD/Direktur buat-langsung.
        $pengusulLvl = $sanksi->pengusul->jabatan?->level?->value ?? 0;
        $pakaiHal2 = ! $sanksi->pengusul->user?->hasRole(Role::Hrd->value)
            && in_array($pengusulLvl, [JabatanLevel::Koordinator->value, JabatanLevel::Kabid->value], true);

        $pengusulChain = [];
        if ($pakaiHal2) {
            $pengusulChain[] = $entri($sanksi->pengusul, 'Pengusul', $sanksi->created_at, 'pengusul');
            foreach ($sanksi->approval as $a) {
                if (in_array($a->peran, [PeranApproval::Koordinator, PeranApproval::Kabid], true)) {
                    $pengusulChain[] = $entri($a->approver, self::labelPeran($a->peran->value), $a->acted_at, 'kabid');
                }
            }
        }

        return [
            'penerbit' => $penerbit,
            'pengusulChain' => $pengusulChain,
            'pakaiHal2' => $pakaiHal2,
        ];
    }

    private static function labelPeran(string $v): string
    {
        return match ($v) {
            'koordinator' => 'Koordinator',
            'kabid' => 'Kabid',
            'hrd' => 'HRD',
            'direktur' => 'Direktur',
            default => ucfirst($v),
        };
    }
}
