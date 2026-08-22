@php
    $bagian = [
        ['id' => 'navigasi', 'judul' => 'Cara Berpindah Halaman'],
        ['id' => 'beranda', 'judul' => 'Beranda'],
        ['id' => 'riwayat', 'judul' => 'Riwayat'],
        ['id' => 'notifikasi', 'judul' => 'Notifikasi'],
        ['id' => 'profil', 'judul' => 'Profil'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Empat layar ini dipakai semua orang, apa pun jabatannya. Menu lain di luar keempatnya
        muncul atau tidak muncul sesuai tugas Anda — kalau sebuah menu tidak Anda lihat, berarti
        peran Anda memang tidak memerlukannya, bukan karena aplikasi rusak.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <h3 class="text-[15px] font-bold mt-4 mb-2">Di HP</h3>
        <p class="text-[14px] leading-relaxed">
            Ada bilah tetap di bagian bawah layar berisi <strong>empat</strong> tombol:
            <strong>Beranda</strong>, <strong>Riwayat</strong>, <strong>Notifikasi</strong>, <strong>Profil</strong>.
            Menu lainnya dicapai lewat kartu-kartu di Beranda.
        </p>
        <p class="text-[13.5px] leading-relaxed mt-2" style="color:var(--text-muted)">
            Bilah bawah otomatis menghilang saat papan ketik terbuka, supaya tidak menutupi kolom isian.
            Itu memang disengaja.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Di komputer</h3>
        <p class="text-[14px] leading-relaxed">
            Menu ada di sisi kiri layar, dikelompokkan: <strong>Operasional</strong>, <strong>SDM</strong>,
            dan <strong>Sistem</strong>. Tiap kelompok bisa dilipat. Tombol garis tiga di kepala menu
            menciutkan sidebar agar layar lebih lega; pilihan itu diingat untuk kunjungan berikutnya.
        </p>

        <x-panduan.gambar src="dasar-bilah-bawah.png" caption="Bilah bawah: Beranda, Riwayat, Notifikasi, Profil" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">
            Beranda adalah <strong>satu-satunya</strong> dasbor. Isinya kartu ringkas per modul, dan kartu yang
            muncul menyesuaikan peran Anda. Tiap kartu bisa ditekan untuk masuk ke modulnya.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Kartu yang mungkin Anda lihat</h3>
        <div class="grid-wrap">
            <table class="table">
                <thead>
                    <tr><th>Kartu</th><th>Muncul untuk</th><th>Isinya</th></tr>
                </thead>
                <tbody>
                    <tr><td><strong>Absensi</strong></td><td>Siapa pun yang punya data karyawan</td><td>Tombol Absen Masuk / Absen Pulang, dan shift Anda hari ini (bisa lebih dari satu bila dinas ganda)</td></tr>
                    <tr><td><strong>Jatah cuti</strong></td><td>Yang boleh mengajukan cuti (Direktur tidak)</td><td>Sisa jatah cuti Anda</td></tr>
                    <tr><td><strong>Sanksi aktif</strong></td><td>Karyawan yang sedang punya sanksi berjalan</td><td>Jumlah sanksi aktif</td></tr>
                    <tr><td><strong>Tiket saya</strong></td><td>Semua karyawan</td><td>Jumlah tiket yang Anda laporkan</td></tr>
                    <tr><td><strong>Antrian tiket</strong></td><td>Anggota tim teknis</td><td>Tiket yang menunggu dikerjakan timmu</td></tr>
                    <tr><td><strong>Inventaris</strong></td><td>Anggota tim teknis</td><td>Aset yang jatuh tempo pemeliharaan</td></tr>
                    <tr><td><strong>Cuti menunggu</strong></td><td>HRD</td><td>Pengajuan cuti se-rumah sakit yang belum selesai</td></tr>
                    <tr><td><strong>Disiplin</strong></td><td>HRD</td><td>Usulan sanksi menunggu dan sanksi yang sudah diterbitkan</td></tr>
                    <tr><td><strong>Kepegawaian</strong></td><td>HRD / Staff HR / Admin Sistem</td><td>Jumlah karyawan aktif, kontrak akan berakhir, kontrak terlewat, karyawan belum tetap, serta daftar pengingat kontrak dan perizinan</td></tr>
                </tbody>
            </table>
        </div>

        <x-panduan.gambar src="dasar-beranda.png" caption="Beranda di HP: kartu absensi, jatah cuti, dan pintasan menu" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <p class="text-[14px] leading-relaxed">
            Riwayat adalah jejak aktivitas <strong>Anda sendiri</strong>, dikumpulkan dari beberapa modul
            dan diurutkan dari yang terbaru. Isinya empat jenis:
        </p>
        <ul class="list-disc ms-5 space-y-1 text-[14px] leading-relaxed mt-2">
            <li><strong>Cuti</strong> — pengajuan, disetujui, ditolak, dibatalkan.</li>
            <li><strong>Absensi</strong> — catatan absen Anda.</li>
            <li><strong>Tiket</strong> — tiket yang Anda laporkan, selesai, atau dibatalkan.</li>
            <li><strong>Sanksi</strong> — sanksi yang diterbitkan, dicabut, atau usulannya ditolak.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Tekan salah satu tombol jenis di atas daftar untuk menyaring; tekan sekali lagi pada tombol yang sama
            untuk menampilkan semuanya kembali. Tiap baris bisa ditekan untuk membuka detailnya.
            Daftar dipecah 20 baris per halaman.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">
            Angka merah kecil pada ikon lonceng menunjukkan jumlah notifikasi yang belum dibaca
            (ditulis <span class="kbd">9+</span> bila lebih dari sembilan).
        </p>
        <ul class="list-disc ms-5 space-y-1 text-[14px] leading-relaxed mt-2">
            <li>Menekan satu notifikasi akan menandainya sudah dibaca dan membawa Anda ke halaman terkait.</li>
            <li>Tombol tandai semua akan mengosongkan angka merah sekaligus.</li>
            <li>Daftar menampilkan 20 notifikasi per halaman, terbaru di atas.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Notifikasi tetap masuk ke halaman ini <strong>walaupun</strong> Anda belum mengaktifkan notifikasi HP.
            Yang membedakan: tanpa notifikasi HP, Anda baru tahu saat membuka aplikasi. Cara mengaktifkannya ada di bab
            <a href="{{ route('panduan.bab', 'instalasi') }}" class="hover:underline" style="color:var(--brand-600)">Memasang Aplikasi di HP</a>.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <p class="text-[14px] leading-relaxed">Halaman Profil memuat:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-2">
            <li><strong>Identitas Anda</strong> — nama, NIP, jabatan, unit. Data ini dikelola bagian kepegawaian; kalau ada yang salah, laporkan ke HRD, bukan diperbaiki sendiri.</li>
            <li><strong>Perizinan</strong> — daftar izin/sertifikat Anda beserta masa berlakunya, supaya terlihat mana yang mendekati kedaluwarsa.</li>
            <li><strong>Kontak</strong> — nomor HP, email pribadi, dan alamat domisili. Bagian ini <strong>boleh</strong> Anda ubah sendiri.</li>
            <li><strong>Kata sandi</strong> — membuat atau mengganti kata sandi.</li>
            <li><strong>Notifikasi &amp; pemasangan aplikasi</strong> — tombol <span class="kbd">Aktifkan Notifikasi</span> dan <span class="kbd">Pasang Aplikasi</span>.</li>
            <li><strong>Keluar</strong> — mengakhiri sesi.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Tombol matahari/bulan di bagian atas layar mengganti tampilan terang ↔ gelap. Pilihan itu diingat.
        </p>

        <x-panduan.gambar src="dasar-profil.png" caption="Halaman Profil" />
    </x-panduan.bagian>

    <div class="divider my-6"></div>
    <div class="text-center mb-4">
        <a href="{{ route('panduan') }}" class="btn btn-sm btn-secondary">← Kembali ke Daftar Isi</a>
    </div>
    <div class="flex gap-2 justify-between text-[13px]">
        @if ($tetangga['sebelum'])
            <a href="{{ route('panduan.bab', $tetangga['sebelum']['slug']) }}" class="btn btn-sm btn-secondary">← {{ $tetangga['sebelum']['judul'] }}</a>
        @else
            <span></span>
        @endif
        @if ($tetangga['sesudah'])
            <a href="{{ route('panduan.bab', $tetangga['sesudah']['slug']) }}" class="btn btn-sm btn-secondary">{{ $tetangga['sesudah']['judul'] }} →</a>
        @endif
    </div>
</x-layouts.panduan>
