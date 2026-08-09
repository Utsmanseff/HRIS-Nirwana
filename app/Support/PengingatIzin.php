<?php

// app/Support/PengingatIzin.php

namespace App\Support;

use App\Enums\SeverityPengingat;
use App\Enums\StatusKaryawan;
use App\Models\IzinKaryawan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pengingat izin karyawan (STR/SIP/SIK/sertifikat) — turunan, tanpa kolom status.
 * Hanya baris TERBARU per (karyawan, jenis) yang dievaluasi; ambang dibaca per jenis.
 * Pola sama dengan PengingatKontrak yang membaca kontrak terakhir.
 */
class PengingatIzin
{
    public function __construct(
        public IzinKaryawan $izin,
        public SeverityPengingat $severity,
        public int $sisaHari,   // negatif = sudah lewat
    ) {}

    /** @return Collection<int, self> */
    public static function semua(?Carbon $hariIni = null): Collection
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();

        return IzinKaryawan::with(['karyawan', 'jenis'])
            ->whereHas('karyawan', fn ($q) => $q->where('status', StatusKaryawan::Aktif->value))
            ->whereHas('jenis', fn ($q) => $q->where('aktif', true))
            ->orderByDesc('berlaku_akhir')
            ->get()
            // Baris pertama tiap (karyawan, jenis) = yang berlaku, sisanya riwayat.
            ->unique(fn (IzinKaryawan $i) => $i->karyawan_id.'|'.$i->jenis_izin_id)
            ->map(fn (IzinKaryawan $i) => self::untuk($i, $hariIni))
            ->filter()
            ->values();
    }

    public static function untuk(IzinKaryawan $izin, ?Carbon $hariIni = null): ?self
    {
        $hariIni = ($hariIni ?? Carbon::today())->startOfDay();

        if ($izin->karyawan?->status !== StatusKaryawan::Aktif || ! $izin->jenis?->aktif) {
            return null;
        }

        $sisaHari = (int) $hariIni->diffInDays($izin->berlaku_akhir->copy()->startOfDay(), false);

        if ($sisaHari < 0) {
            return new self($izin, SeverityPengingat::Terlewat, $sisaHari);
        }

        if ($sisaHari <= $izin->jenis->ambang_hari) {
            return new self($izin, SeverityPengingat::AkanBerakhir, $sisaHari);
        }

        return null;
    }
}
