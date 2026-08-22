@php
    $bagian = [
        ['id' => 'role', 'judul' => 'Delapan Role dan Artinya'],
        ['id' => 'derived', 'judul' => 'Wewenang yang TIDAK Datang dari Role'],
        ['id' => 'kelola', 'judul' => 'Mengelola Akun Pengguna'],
        ['id' => 'jangan', 'judul' => 'Hal yang Jangan Dilakukan di Server'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Menu <strong>Sistem → Pengguna &amp; Role</strong> mengatur akun dan kewenangannya.
        Sebelum memakainya, pahami dulu satu hal pokok: <strong>tidak semua wewenang datang dari role.</strong>
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <div class="grid-wrap">
            <table class="table">
                <thead><tr><th>Role</th><th>Wewenangnya</th></tr></thead>
                <tbody>
                    <tr><td><strong>Karyawan</strong></td><td>Dasar: melihat data sendiri, absen, mengajukan cuti. Diberikan otomatis saat seseorang mengklaim akunnya.</td></tr>
                    <tr><td><strong>Staff HR</strong></td><td>Karyawan + mengelola data SDM + melihat laporan.</td></tr>
                    <tr><td><strong>HRD</strong></td><td>Staff HR + <strong>tahap akhir persetujuan cuti</strong>, kelola cuti, kelola dan cabut sanksi.</td></tr>
                    <tr><td><strong>IT</strong></td><td>Karyawan + mengerjakan tiket IT dan inventaris IT.</td></tr>
                    <tr><td><strong>Teknisi</strong></td><td>Karyawan + mengerjakan tiket dan inventaris Sarana.</td></tr>
                    <tr><td><strong>ATEM</strong></td><td>Karyawan + mengerjakan tiket dan inventaris alat kesehatan.</td></tr>
                    <tr><td><strong>Direktur</strong></td><td>Melihat laporan + tahap akhir penerbitan sanksi. Tidak ikut alur cuti, jadwal, dan sanksi sebagai pegawai.</td></tr>
                    <tr><td><strong>Admin Sistem</strong></td><td>Akses penuh ke seluruh aplikasi tanpa perlu izin satu per satu.</td></tr>
                </tbody>
            </table>
        </div>

        <x-panduan.catatan tipe="bahaya">
            Role <strong>HRD</strong> dan <strong>Direktur</strong> harus dipegang <strong>tepat satu orang</strong>.
            Sistem hanya memakai satu pemegang saat menyusun rantai persetujuan cuti dan sanksi; pemegang kedua
            akan diabaikan tanpa pemberitahuan. Sebaliknya, bila kedua role itu <strong>belum ada pemegangnya</strong>,
            usulan sanksi akan <strong>ditolak</strong> karena tidak punya penyetuju.
        </x-panduan.catatan>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">
            Sebagian wewenang <strong>dihitung dari struktur organisasi</strong>, bukan diberikan lewat role.
            Memberi role tidak akan memunculkannya, dan mencabut role tidak akan menghilangkannya.
        </p>
        <div class="grid-wrap mt-3">
            <table class="table">
                <thead><tr><th>Wewenang</th><th>Datang dari</th></tr></thead>
                <tbody>
                    <tr><td>Menyetujui cuti</td><td>Punya bawahan menurut struktur — atau memegang role HRD.</td></tr>
                    <tr><td>Mengusulkan sanksi</td><td>Punya bawahan, atau memegang jabatan bertanda pengawas.</td></tr>
                    <tr><td>Menyusun jadwal shift</td><td>Memegang jabatan pimpinan (tingkat koordinator ke atas).</td></tr>
                    <tr><td>Melihat laporan absensi unit</td><td>Memimpin unit — cakupannya sebatas unit itu dan turunannya.</td></tr>
                    <tr><td>Mengerjakan tiket &amp; inventaris</td><td>Role tim teknis: IT, Teknisi, atau ATEM.</td></tr>
                </tbody>
            </table>
        </div>
        <p class="text-[14px] leading-relaxed mt-3">
            Jadi bila seorang koordinator mengeluh tidak bisa menyetujui cuti bawahannya,
            <strong>jangan menambahkan role</strong>. Periksa strukturnya: apakah jabatannya benar,
            apakah unitnya benar, apakah bawahannya benar-benar ditempatkan di unit itu.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <p class="text-[14px] leading-relaxed">Dari halaman Pengguna &amp; Role, Anda bisa:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-2">
            <li><strong>Membuatkan akun</strong> untuk karyawan aktif yang belum punya akun.</li>
            <li><strong>Memberi atau mencabut role</strong>. Muncul pesan <span class="kbd">Role tersimpan.</span></li>
            <li><strong>Mereset kata sandi</strong> sebuah akun.</li>
            <li><strong>Menonaktifkan akun</strong>. Efeknya langsung: sesi orang itu diputus dan dia dikembalikan ke halaman masuk dengan pesan akun dinonaktifkan.</li>
            <li><strong>Melepas tautan</strong> akun dari data karyawan, misalnya bila akun terlanjur mengklaim data yang salah. Setelah dilepas, data karyawan itu bisa diklaim ulang.</li>
        </ul>

        <x-panduan.catatan tipe="awas">
            Anda <strong>tidak bisa mengubah akun sendiri</strong> dari halaman ini
            (<span class="kbd">Tidak bisa mengubah akun sendiri.</span>). Ini pengaman agar tidak ada Admin
            Sistem yang tanpa sengaja mengunci dirinya sendiri. Bila memang perlu, mintalah Admin Sistem lain.
        </x-panduan.catatan>

        {{-- SS: kelola pengguna dan role --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <x-panduan.peran :peran="['Admin Sistem']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Bagian ini khusus untuk yang mengurus server produksi.
        </p>

        <x-panduan.catatan tipe="bahaya">
            <strong>Jangan menjalankan pembuatan ulang kunci aplikasi (<span class="kbd">key:generate</span>) di server produksi.</strong>
            Kunci itu dipakai membaca sesi login dan menentukan alamat internal komponen halaman.
            Menggantinya membuat semua orang terlempar keluar dan sebagian besar tombol berhenti berfungsi,
            walau halamannya tetap terbuka.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="bahaya">
            <strong>Jangan mengubah struktur database produksi secara manual.</strong>
            Semua perubahan skema harus lewat migrasi resmi aplikasi. Perubahan manual membuat versi database
            tidak lagi cocok dengan aplikasinya, dan pembaruan berikutnya bisa gagal di tengah jalan.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Selain itu: notifikasi push memerlukan kunci notifikasi yang terpasang di server. Bila pengguna
            melaporkan pesan <span class="kbd">Notifikasi belum aktif di server</span>, itu urusan konfigurasi
            server — bukan kesalahan perangkat mereka.
        </p>
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
