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
            // Grup "Administrasi" (bab sdm & sistem) sengaja TIDAK ada di sini: panduan
            // terbuka untuk semua, termasuk tamu, sehingga alur kerja HRD/Direktur dan
            // pengelolaan akun tak layak dipajang. Materinya disampaikan terpisah.
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

    /**
     * Peta nama rute → "bab#bagian". Kunci boleh nama rute persis atau prefix-nya:
     * pencocokan memakai prefix TERPANJANG, pola sama NavMenu::aktif(). Rute yang
     * tak punya prefix di sini tak mendapat tombol "?".
     *
     * Nilai null = PENYEKAT: rute itu (dan turunannya) sengaja tanpa tombol walau
     * induknya punya. Dibutuhkan karena halaman kerja HRD/Direktur bersarang di
     * bawah prefix yang sama dengan halaman karyawan (cuti.kelola di bawah cuti),
     * sedangkan materinya sengaja dibuang dari panduan.
     *
     * @return array<string,?string>
     */
    public static function rute(): array
    {
        return [
            'beranda' => 'dasar#beranda',
            'riwayat' => 'dasar#riwayat',
            'notifikasi' => 'dasar#notifikasi',
            'profil' => 'dasar#profil',

            'absensi' => 'absensi#masuk',
            'absensi.jadwal-saya' => 'jadwal-saya#membaca',
            'absensi.jadwal' => 'jadwal-shift#shift',
            'absensi.laporan' => 'laporan-absensi#filter',

            'cuti' => 'cuti#jatah',
            'cuti.ajukan' => 'cuti#ajukan',
            'cuti.detail' => 'cuti#status',
            'cuti.persetujuan' => 'cuti#menyetujui',

            'pengganti' => 'pengganti#kapan',

            'disiplin' => 'disiplin#mengusulkan',
            'disiplin.saya' => 'disiplin#sanksi-saya',
            'disiplin.persetujuan' => 'disiplin#rantai',

            'tiket' => 'tiket#memantau',
            'tiket.buat' => 'tiket#melapor',
            'tiket.laporan' => 'tiket#laporan',

            'inventaris' => 'inventaris#siapa',
            'inventaris.kategori' => 'inventaris#kategori',
            'inventaris.tambah' => 'inventaris#tambah',
            'inventaris.ubah' => 'inventaris#tambah',
            'inventaris.detail' => 'inventaris#detail',
            'inventaris.laporan' => 'inventaris#laporan',

            // Penyekat — halaman kerja HRD/Direktur, tak dibahas di panduan.
            'absensi.pengaturan' => null,
            'cuti.kelola' => null,
            'cuti.laporan' => null,
            'disiplin.kelola' => null,
            'disiplin.laporan' => null,
        ];
    }

    /**
     * Target panduan untuk sebuah nama rute.
     *
     * @return array{slug:string,bagian:string}|null
     */
    public static function untukRute(?string $routeName): ?array
    {
        if (! $routeName) {
            return null;
        }

        $terbaik = null;
        $panjang = -1;
        foreach (self::rute() as $rute => $target) {
            if (($routeName === $rute || str_starts_with($routeName, $rute.'.')) && strlen($rute) > $panjang) {
                $terbaik = $target;
                $panjang = strlen($rute);
            }
        }

        // null bisa berarti dua hal — tak ada prefix yang cocok, atau prefix
        // terdekat memang penyekat. Keduanya berujung sama: tanpa tombol.
        if ($terbaik === null) {
            return null;
        }

        [$slug, $bagian] = array_pad(explode('#', $terbaik, 2), 2, null);

        return ['slug' => $slug, 'bagian' => $bagian];
    }
}
