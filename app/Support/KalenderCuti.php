<?php

namespace App\Support;

use App\Enums\StatusPengajuanCuti;
use App\Models\PengajuanCuti;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class KalenderCuti
{
    /**
     * Peta hari→daftar cuti untuk satu bulan.
     *
     * @param  array<int>  $unitIds  org_unit_id yang tercakup (sudah termasuk turunan)
     * @return array{awal:Carbon,akhir:Carbon,hari:array<string,Collection<int,array{pengajuan_id:int,nama:string,nip:string,jenis:string,status:string}>>}
     */
    public static function bulan(array $unitIds, Carbon $anchor): array
    {
        $awal = $anchor->copy()->startOfMonth();
        $akhir = $anchor->copy()->endOfMonth();
        $hari = [];

        if (! empty($unitIds)) {
            $pengajuan = PengajuanCuti::query()
                ->whereIn('status', [
                    StatusPengajuanCuti::Disetujui->value,
                    StatusPengajuanCuti::Diajukan->value,
                    StatusPengajuanCuti::Diproses->value,
                ])
                ->whereDate('tanggal_mulai', '<=', $akhir)
                ->whereDate('tanggal_selesai', '>=', $awal)
                ->whereHas('karyawan', fn ($k) => $k->whereIn('org_unit_id', $unitIds))
                ->with(['karyawan:id,nama_lengkap,nip,org_unit_id', 'jenisCuti'])
                ->get();

            foreach ($pengajuan as $p) {
                $mulai = $p->tanggal_mulai->greaterThan($awal) ? $p->tanggal_mulai->copy() : $awal->copy();
                $selesai = $p->tanggal_selesai->lessThan($akhir) ? $p->tanggal_selesai->copy() : $akhir->copy();

                for ($d = $mulai; $d->lte($selesai); $d->addDay()) {
                    $key = $d->format('Y-m-d');
                    $hari[$key] ??= new Collection;
                    $hari[$key]->push([
                        'pengajuan_id' => $p->id,
                        'nama' => $p->karyawan->nama_lengkap,
                        'nip' => $p->karyawan->nip,
                        'jenis' => $p->jenisCuti->nama,
                        'status' => $p->status->value,
                    ]);
                }
            }
        }

        return ['awal' => $awal, 'akhir' => $akhir, 'hari' => $hari];
    }
}
