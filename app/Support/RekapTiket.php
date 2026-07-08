<?php

namespace App\Support;

use App\Enums\StatusTiket;
use App\Models\Tiket;
use Illuminate\Database\Eloquent\Builder;

class RekapTiket
{
    /**
     * @param  array{tim?:list<string>|null,status?:string|null,prioritas?:string|null,jenis?:string|null,dari?:string|null,sampai?:string|null}  $f
     */
    public static function query(array $f): Builder
    {
        return Tiket::query()
            ->with(['aset', 'pelapor', 'penyelesai'])
            ->when(! empty($f['tim']), fn ($q) => $q->tim($f['tim']))
            ->when(! empty($f['status']), fn ($q) => $q->where('status', $f['status']))
            ->when(! empty($f['prioritas']), fn ($q) => $q->where('prioritas', $f['prioritas']))
            ->when(! empty($f['jenis']), fn ($q) => $q->where('jenis', $f['jenis']))
            ->when(! empty($f['dari']), fn ($q) => $q->whereDate('waktu_lapor', '>=', $f['dari']))
            ->when(! empty($f['sampai']), fn ($q) => $q->whereDate('waktu_lapor', '<=', $f['sampai']))
            ->orderByDesc('waktu_lapor');
    }

    /** Jumlah tiket aktif (baru+diproses) untuk tim tsb. */
    public static function jumlahAntrian(?array $timNilai): int
    {
        return Tiket::query()
            ->when(! empty($timNilai), fn ($q) => $q->tim($timNilai))
            ->whereIn('status', array_map(fn ($s) => $s->value, StatusTiket::aktif()))
            ->count();
    }

    /** Jumlah tiket aktif yang dilaporkan karyawan tsb. */
    public static function jumlahTiketSaya(int $karyawanId): int
    {
        return Tiket::where('pelapor_id', $karyawanId)
            ->whereIn('status', array_map(fn ($s) => $s->value, StatusTiket::aktif()))
            ->count();
    }

    /**
     * Rata-rata menit respon & penyelesaian per tim (dari tiket yang punya waktu terkait).
     *
     * @return list<array{tim:string,rata_respon:float|null,rata_penyelesaian:float|null,jumlah:int}>
     */
    public static function metrikPerTim(array $f): array
    {
        $tiket = self::query($f)->reorder()->get();

        return $tiket->groupBy(fn ($t) => $t->tim->value)->map(function ($grup, $tim) {
            $respon = $grup->map->menitRespon()->filter(fn ($v) => $v !== null);
            $selesai = $grup->map->menitPenyelesaian()->filter(fn ($v) => $v !== null);

            return [
                'tim' => $tim,
                'rata_respon' => $respon->isNotEmpty() ? (float) $respon->avg() : null,
                'rata_penyelesaian' => $selesai->isNotEmpty() ? (float) $selesai->avg() : null,
                'jumlah' => $grup->count(),
            ];
        })->values()->all();
    }
}
