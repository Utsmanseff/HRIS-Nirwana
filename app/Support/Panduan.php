<?php

namespace App\Support;

class Panduan
{
    /**
     * Registry bab panduan — source of truth. Slug = nama view di
     * resources/views/panduan/{slug}.blade.php DAN segmen URL /panduan/{slug}.
     * 'peran' murni informatif (badge di daftar isi): halaman panduan terbuka
     * untuk semua orang, termasuk tamu yang belum punya akun.
     *
     * @return list<array{slug:string,judul:string,ringkas:string,ikon:string,peran:list<string>,grup:string}>
     */
    public static function semua(): array
    {
        return [
            // 'masuk' sengaja SEBELUM 'instalasi': tombol Pasang Aplikasi ada di halaman
            // Profil, yang baru bisa dibuka setelah login. Menyuruh orang memasang lebih
            // dulu berarti menyuruh membuka layar yang belum bisa mereka capai.
            ['slug' => 'masuk', 'judul' => 'Masuk & Klaim Akun', 'ringkas' => 'Login pertama kali, klaim akun karyawan, dan ganti kata sandi.', 'ikon' => 'shield', 'peran' => ['Semua'], 'grup' => 'Mulai'],
            ['slug' => 'instalasi', 'judul' => 'Memasang Aplikasi di HP', 'ringkas' => 'Setelah bisa masuk: pasang NirwanaHRIS di layar utama dan nyalakan notifikasi.', 'ikon' => 'home', 'peran' => ['Semua'], 'grup' => 'Mulai'],
            ['slug' => 'dasar', 'judul' => 'Beranda, Riwayat, Notifikasi, Profil', 'ringkas' => 'Empat layar yang dipakai semua orang setiap hari.', 'ikon' => 'home', 'peran' => ['Semua'], 'grup' => 'Mulai'],
            ['slug' => 'absensi', 'judul' => 'Absen Masuk & Pulang', 'ringkas' => 'Absen swipe dengan lokasi dan foto wajah, plus arti telat dan pulang cepat.', 'ikon' => 'clock', 'peran' => ['Karyawan'], 'grup' => 'Absensi'],
            ['slug' => 'jadwal-saya', 'judul' => 'Jadwal Saya', 'ringkas' => 'Melihat shift sendiri dan arti dinas ganda.', 'ikon' => 'calendar', 'peran' => ['Karyawan'], 'grup' => 'Absensi'],
            ['slug' => 'jadwal-shift', 'judul' => 'Menyusun Jadwal Shift', 'ringkas' => 'Grid jadwal, pola bernama, dan menerapkan pola ke anggota unit.', 'ikon' => 'calendar', 'peran' => ['Kepala Unit'], 'grup' => 'Absensi'],
            ['slug' => 'laporan-absensi', 'judul' => 'Laporan Absensi', 'ringkas' => 'Rekap kehadiran, foto absen, dan ekspor PDF/Excel.', 'ikon' => 'chart', 'peran' => ['Kepala Unit', 'HRD', 'Staff HR', 'Direktur'], 'grup' => 'Absensi'],
            ['slug' => 'cuti', 'judul' => 'Cuti', 'ringkas' => 'Mengajukan cuti, alur persetujuan berjenjang, surat cuti ber-QR.', 'ikon' => 'calendar', 'peran' => ['Karyawan', 'Kepala Unit', 'HRD', 'Direktur'], 'grup' => 'Operasional'],
            ['slug' => 'pengganti', 'judul' => 'Pengganti Jadwal', 'ringkas' => 'Menutup lowongan shift saat anggota cuti atau nonaktif.', 'ikon' => 'users', 'peran' => ['Kepala Unit'], 'grup' => 'Operasional'],
            ['slug' => 'disiplin', 'judul' => 'Disiplin & Surat Peringatan', 'ringkas' => 'Mengusulkan sanksi, rantai persetujuan, surat SP ber-QR, dan sanksi milik sendiri.', 'ikon' => 'gavel', 'peran' => ['Karyawan', 'Kepala Unit', 'Pengawas (SPI)', 'HRD', 'Direktur'], 'grup' => 'Operasional'],
            ['slug' => 'tiket', 'judul' => 'Tiket IT / Sarana / Alkes', 'ringkas' => 'Melapor kerusakan dan menangani tiket sebagai tim teknis.', 'ikon' => 'ticket', 'peran' => ['Semua', 'Tim Teknis'], 'grup' => 'Operasional'],
            ['slug' => 'inventaris', 'judul' => 'Inventaris Aset', 'ringkas' => 'Mendata aset, kategori, pemeliharaan, dan laporannya.', 'ikon' => 'box', 'peran' => ['Tim Teknis'], 'grup' => 'Operasional'],
            ['slug' => 'sdm', 'judul' => 'Data Karyawan & Struktur Organisasi', 'ringkas' => 'Kelola karyawan, kontrak, dokumen, unit, jabatan, dan jenis izin.', 'ikon' => 'users', 'peran' => ['HRD', 'Staff HR', 'Admin Sistem'], 'grup' => 'Administrasi'],
            ['slug' => 'sistem', 'judul' => 'Pengguna & Role', 'ringkas' => 'Memberi role, menonaktifkan akun, dan arti tiap role.', 'ikon' => 'shield', 'peran' => ['Admin Sistem'], 'grup' => 'Administrasi'],
        ];
    }

    /** Bab dengan slug tertentu, atau null bila tak ada. */
    public static function cari(string $slug): ?array
    {
        foreach (self::semua() as $bab) {
            if ($bab['slug'] === $slug) {
                return $bab;
            }
        }

        return null;
    }

    /** Bab dikelompokkan menurut 'grup'; urutan grup mengikuti kemunculan pertama. */
    public static function perGrup(): array
    {
        $out = [];
        foreach (self::semua() as $bab) {
            $out[$bab['grup']][] = $bab;
        }

        return $out;
    }

    /** Bab sebelum & sesudah — untuk navigasi "Sebelumnya / Berikutnya". */
    public static function tetangga(string $slug): array
    {
        $semua = self::semua();
        $i = array_search($slug, array_column($semua, 'slug'), true);

        if ($i === false) {
            return ['sebelum' => null, 'sesudah' => null];
        }

        return [
            'sebelum' => $i > 0 ? $semua[$i - 1] : null,
            'sesudah' => $i < count($semua) - 1 ? $semua[$i + 1] : null,
        ];
    }
}
