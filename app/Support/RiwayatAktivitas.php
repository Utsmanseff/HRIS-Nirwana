<?php

namespace App\Support;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SanksiDisiplin;
use App\Models\Tiket;
use Illuminate\Support\Collection;

/**
 * Feed aktivitas milik SATU karyawan, digabung lintas modul & diurut per-waktu.
 * Tiap event dinormalkan ke: {waktu, jenis, ikon, warna, judul, detail, url}.
 * warna ∈ brand|success|warning|danger|neutral (dipetakan ke kelas di blade).
 *
 * Bukan audit-log: tiap entitas jadi SATU event yang mencerminkan status terakhirnya
 * (waktu = perubahan status terkini). Cukup untuk riwayat pribadi, tanpa tabel jejak.
 */
class RiwayatAktivitas
{
    /** Batas ambil per-sumber sebelum digabung (feed pribadi = cukup yang terbaru). */
    private const BATAS_SUMBER = 100;

    /** @var list<string> */
    public const JENIS = ['cuti', 'tiket', 'sanksi', 'absensi'];

    /**
     * @param  string|null  $jenis  filter satu jenis; null = semua
     * @return Collection<int, array{waktu:\Illuminate\Support\Carbon,jenis:string,ikon:string,warna:string,judul:string,detail:string,url:?string}>
     */
    public static function untuk(Karyawan $karyawan, ?string $jenis = null): Collection
    {
        $events = collect();

        if ($jenis === null || $jenis === 'cuti') {
            $events = $events->concat(self::cuti($karyawan));
        }
        if ($jenis === null || $jenis === 'tiket') {
            $events = $events->concat(self::tiket($karyawan));
        }
        if ($jenis === null || $jenis === 'sanksi') {
            $events = $events->concat(self::sanksi($karyawan));
        }
        if ($jenis === null || $jenis === 'absensi') {
            $events = $events->concat(self::absensi($karyawan));
        }

        return $events->sortByDesc('waktu')->values();
    }

    private static function cuti(Karyawan $karyawan): Collection
    {
        return PengajuanCuti::where('karyawan_id', $karyawan->id)
            ->with('jenisCuti')
            ->latest('updated_at')
            ->limit(self::BATAS_SUMBER)
            ->get()
            ->map(function (PengajuanCuti $c) {
                [$judul, $warna] = match ($c->status->value) {
                    'disetujui' => ['Cuti disetujui', 'success'],
                    'ditolak' => ['Pengajuan cuti ditolak', 'danger'],
                    'dibatalkan' => ['Cuti dibatalkan', 'neutral'],
                    default => ['Mengajukan '.($c->jenisCuti?->nama ?? 'cuti'), 'brand'],
                };

                $mulai = $c->tanggal_mulai->translatedFormat('j M');
                $selesai = $c->tanggal_selesai->translatedFormat('j M Y');
                $tgl = $c->tanggal_mulai->isSameDay($c->tanggal_selesai)
                    ? $c->tanggal_selesai->translatedFormat('j M Y')
                    : $mulai.'–'.$selesai;

                return [
                    'waktu' => $c->updated_at,
                    'jenis' => 'cuti',
                    'ikon' => 'calendar',
                    'warna' => $warna,
                    'judul' => $judul,
                    'detail' => ($c->jenisCuti?->nama ?? 'Cuti').' · '.$tgl.' · '.$c->jumlah_hari.' hari',
                    'url' => '/cuti/'.$c->id,
                ];
            });
    }

    private static function tiket(Karyawan $karyawan): Collection
    {
        return Tiket::where('pelapor_id', $karyawan->id)
            ->latest('waktu_lapor')
            ->limit(self::BATAS_SUMBER)
            ->get()
            ->map(function (Tiket $t) {
                $selesai = $t->status->value === 'selesai';
                $batal = $t->status->value === 'batal';

                return [
                    'waktu' => $selesai && $t->waktu_selesai ? $t->waktu_selesai : $t->waktu_lapor,
                    'jenis' => 'tiket',
                    'ikon' => 'ticket',
                    'warna' => $selesai ? 'success' : ($batal ? 'neutral' : 'brand'),
                    'judul' => $selesai ? 'Tiket selesai' : ($batal ? 'Tiket dibatalkan' : 'Melaporkan tiket'),
                    'detail' => $t->nomor.' · '.$t->judul,
                    'url' => '/tiket/'.$t->id,
                ];
            });
    }

    private static function sanksi(Karyawan $karyawan): Collection
    {
        // Hanya tahap yang menyentuh karyawan: terbit / cabut / tolak. Tahap usul internal disembunyikan.
        return SanksiDisiplin::where('karyawan_id', $karyawan->id)
            ->whereIn('status', ['diterbitkan', 'ditolak', 'dicabut'])
            ->latest('updated_at')
            ->limit(self::BATAS_SUMBER)
            ->get()
            ->map(function (SanksiDisiplin $s) {
                [$judul, $warna, $waktu] = match ($s->status->value) {
                    'dicabut' => ['Sanksi dicabut', 'success', $s->dicabut_pada ?? $s->updated_at],
                    'ditolak' => ['Usulan sanksi ditolak', 'neutral', $s->updated_at],
                    default => ['Sanksi diterbitkan', 'danger', $s->tanggal_terbit ?? $s->updated_at],
                };

                return [
                    'waktu' => $waktu,
                    'jenis' => 'sanksi',
                    'ikon' => 'gavel',
                    'warna' => $warna,
                    'judul' => $judul,
                    'detail' => \Illuminate\Support\Str::limit($s->uraian, 70),
                    'url' => '/disiplin/saya',
                ];
            });
    }

    private static function absensi(Karyawan $karyawan): Collection
    {
        return Absensi::where('karyawan_id', $karyawan->id)
            ->latest('tanggal_kerja')
            ->limit(self::BATAS_SUMBER)
            ->get()
            ->map(function (Absensi $a) {
                [$label, $badge] = $a->labelStatus();
                $warna = str_contains($badge, 'danger') ? 'danger'
                    : (str_contains($badge, 'warning') ? 'warning' : 'success');

                $masuk = $a->jam_masuk?->format('H:i') ?? '—';
                $pulang = $a->jam_pulang?->format('H:i') ?? 'aktif';
                $detail = "Masuk $masuk · Pulang $pulang";
                if ($a->jam_pulang) {
                    $detail .= ' · '.$a->jamKerjaLabel();
                }

                return [
                    'waktu' => $a->jam_masuk ?? $a->tanggal_kerja,
                    'jenis' => 'absensi',
                    'ikon' => 'clock',
                    'warna' => $warna,
                    'judul' => 'Absensi · '.$label,
                    'detail' => $detail,
                    'url' => null,
                ];
            });
    }
}
