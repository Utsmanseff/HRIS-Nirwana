<?php

namespace App\Support;

use App\Enums\StatusPengajuanCuti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RekapCuti
{
    /**
     * Query pengajuan ter-filter (dengan relasi + urutan) untuk tabel & ekspor.
     *
     * @param  array{dari?:string,sampai?:string,unit_id?:int|string|null,jenis_id?:int|string|null,status?:string|null}  $f
     */
    public static function query(array $f): Builder
    {
        return PengajuanCuti::query()
            ->with(['karyawan.orgUnit', 'jenisCuti'])
            ->when(! empty($f['dari']), fn ($q) => $q->whereDate('tanggal_mulai', '>=', $f['dari']))
            ->when(! empty($f['sampai']), fn ($q) => $q->whereDate('tanggal_mulai', '<=', $f['sampai']))
            ->when(! empty($f['unit_id']), fn ($q) => $q->whereHas('karyawan',
                fn ($k) => $k->whereIn('org_unit_id', OrgUnit::denganTurunan((int) $f['unit_id']))))
            ->when(! empty($f['jenis_id']), fn ($q) => $q->where('jenis_cuti_id', $f['jenis_id']))
            ->when(! empty($f['status']) && $f['status'] !== 'semua', fn ($q) => $q->where('status', $f['status']))
            ->orderByDesc('tanggal_mulai');
    }

    /**
     * Jumlah per status (abaikan filter status). Semua status hadir (default 0).
     *
     * @return array<string,int>
     */
    public static function hitungStatus(array $f): array
    {
        $dasar = array_fill_keys(
            array_map(fn ($s) => $s->value, StatusPengajuanCuti::cases()),
            0,
        );

        $fTanpaStatus = $f;
        unset($fTanpaStatus['status']);

        $hitung = self::query($fTanpaStatus)
            ->reorder()
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        foreach ($hitung as $status => $n) {
            $dasar[$status] = (int) $n;
        }

        return $dasar;
    }

    /** @return Collection<int,PengajuanCuti> */
    public static function daftarPengajuan(array $f): Collection
    {
        return self::query($f)->get();
    }

    /**
     * Saldo per karyawan aktif & eligible (jatah/terpakai/sisa periode berjalan).
     *
     * @return Collection<int,array{nama:string,nip:string,unit:?string,eligible:bool,jatah:int,terpakai:int,sisa:int}>
     */
    public static function saldoKaryawan(?int $unitId): Collection
    {
        return Karyawan::query()->aktif()
            ->with('orgUnit')
            ->when($unitId, fn ($q) => $q->whereIn('org_unit_id', OrgUnit::denganTurunan($unitId)))
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function (Karyawan $k) {
                $s = SaldoCuti::untuk($k);

                return [
                    'nama' => $k->nama_lengkap,
                    'nip' => $k->nip,
                    'unit' => $k->orgUnit?->nama,
                    'eligible' => $s->eligible(),
                    'jatah' => $s->jatah(),
                    'terpakai' => $s->terpakai(),
                    'sisa' => $s->efektif(),
                ];
            })
            ->filter(fn ($r) => $r['eligible'])
            ->values();
    }

    /** Total pending (diajukan+diproses) seluruh org, tanpa filter periode. */
    public static function jumlahPendingOrgWide(): int
    {
        return PengajuanCuti::query()
            ->whereIn('status', [StatusPengajuanCuti::Diajukan->value, StatusPengajuanCuti::Diproses->value])
            ->count();
    }
}
