@php
    $bagian = [
        ['id' => 'syarat', 'judul' => 'Yang Perlu Disiapkan'],
        ['id' => 'android', 'judul' => 'Memasang di Android'],
        ['id' => 'ios', 'judul' => 'Memasang di iPhone / iPad'],
        ['id' => 'notifikasi', 'judul' => 'Menyalakan Notifikasi'],
        ['id' => 'masalah', 'judul' => 'Kalau Ada Masalah'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Bab ini dikerjakan <strong>setelah</strong> Anda bisa masuk. Kalau belum, baca dulu
        <a href="{{ route('panduan.bab', 'masuk') }}" class="hover:underline" style="color:var(--brand-600)">Masuk &amp; Klaim Akun</a> —
        tombol pemasangan di Android berada di halaman <strong>Profil</strong>, yang baru terbuka setelah login.
    </p>
    <p class="text-[14px] leading-relaxed mt-3">
        NirwanaHRIS adalah aplikasi web. Anda tidak perlu mencarinya di Play Store atau App Store —
        cukup pasang halaman yang sudah Anda buka itu ke layar utama, supaya bisa dijalankan
        seperti aplikasi biasa: layar penuh, tanpa bilah alamat, dan bisa menerima notifikasi.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed">
            <li>HP dengan koneksi internet.</li>
            <li>Alamat aplikasi: <span class="kbd">presensi.rsunirwana.id</span></li>
            <li><strong>Akun yang sudah bisa masuk</strong> — dan sudah diklaim ke data karyawan Anda.</li>
            <li><strong>Android:</strong> browser Chrome.</li>
            <li><strong>iPhone / iPad:</strong> <strong>Safari</strong> atau <strong>Chrome</strong> — keduanya bisa, karena pemasangan dilakukan lewat menu <strong>Bagikan</strong> milik iPhone, bukan lewat browsernya.</li>
        </ul>

        <x-panduan.catatan tipe="info">
            Anda boleh memakai aplikasi lewat browser biasa tanpa memasangnya. Tetapi tanpa dipasang,
            notifikasi pengingat absen dan pemberitahuan cuti tidak akan sampai ke HP Anda —
            khususnya di iPhone, yang memang hanya mengizinkan notifikasi untuk aplikasi yang sudah dipasang.
        </x-panduan.catatan>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <h3 class="text-[15px] font-bold mt-4 mb-2">Cara utama — lewat halaman Profil</h3>
        <x-panduan.langkah>
            <li>Buka aplikasi di <strong>Chrome</strong> dan <strong>masuk</strong> dengan akun Anda.</li>
            <li>Ketuk <strong>Profil</strong> di bilah bawah.</li>
            <li>Ketuk tombol <span class="kbd">Pasang Aplikasi</span>.</li>
            <li>Ketuk <strong>Instal</strong> saat Chrome meminta konfirmasi.</li>
            <li>Ikon NirwanaHRIS muncul di layar utama HP. Mulai sekarang bukalah dari ikon itu, bukan dari browser.</li>
        </x-panduan.langkah>

        <x-panduan.catatan tipe="info">
            Tombol <span class="kbd">Pasang Aplikasi</span> hanya muncul bila Chrome menawarkan pemasangan.
            Ia <strong>tidak akan terlihat</strong> jika aplikasi sudah terpasang, atau bila browsernya memang
            tidak mendukung. Kalau tombolnya tidak ada, pakai cara cadangan di bawah.
        </x-panduan.catatan>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Cara cadangan — lewat menu Chrome</h3>
        <x-panduan.langkah>
            <li>Ketuk menu titik tiga <span class="kbd">⋮</span> di pojok kanan atas Chrome.</li>
            <li>Pilih <strong>Instal aplikasi</strong> atau <strong>Tambahkan ke Layar Utama</strong> (namanya berbeda tergantung versi Chrome).</li>
            <li>Ketuk <strong>Instal</strong> / <strong>Tambah</strong> saat diminta konfirmasi.</li>
        </x-panduan.langkah>

        {{-- SS: menu Instal aplikasi di Chrome Android --}}
        {{-- SS: ikon NirwanaHRIS di layar utama Android --}}

        <x-panduan.gambar src="instalasi-pasang.png" caption="Tombol Pasang Aplikasi di halaman Profil — muncul bila Chrome menawarkan pemasangan" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <x-panduan.langkah>
            <li>Buka <span class="kbd">presensi.rsunirwana.id</span> di <strong>Safari</strong> atau <strong>Chrome</strong>. iPhone tidak punya tombol pasang di dalam aplikasi, jadi langkahnya lewat menu Bagikan.</li>
            <li>Ketuk tombol <strong>Bagikan</strong> — ikon kotak dengan panah ke atas. Di <strong>Safari</strong> ada di bilah bawah; di <strong>Chrome</strong> ketuk menu titik tiga <span class="kbd">•••</span> lebih dulu, lalu pilih <strong>Bagikan</strong>.</li>
            <li>Gulir daftarnya, pilih <strong>Tambah ke Layar Utama</strong>.</li>
            <li>Ketuk <strong>Tambah</strong> di pojok kanan atas.</li>
            <li>Buka aplikasi dari ikon NirwanaHRIS di layar utama. Layarnya akan penuh, tanpa bilah alamat browser.</li>
        </x-panduan.langkah>

        <x-panduan.catatan tipe="awas">
            Langkah ini <strong>wajib</strong> di iPhone kalau Anda ingin menerima notifikasi. Selama aplikasi
            masih dibuka lewat tab browser, iPhone tidak akan mengizinkan notifikasi sama sekali.
        </x-panduan.catatan>

        {{-- SS: menu Bagikan di Chrome iOS --}}
        {{-- SS: menu Tambah ke Layar Utama di lembar Bagikan iOS --}}
        {{-- SS: konfirmasi Tambah ke Layar Utama --}}
        {{-- SS: ikon NirwanaHRIS di layar utama iPhone --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <x-panduan.langkah>
            <li>Buka aplikasi <strong>dari ikon di layar utama</strong> (bukan dari tab browser).</li>
            <li>Masuk lagi bila diminta — aplikasi yang baru dipasang punya sesinya sendiri.</li>
            <li>Ketuk <strong>Profil</strong> di bilah bawah.</li>
            <li>Ketuk tombol <span class="kbd">Aktifkan Notifikasi</span>.</li>
            <li>HP akan menanyakan izin — pilih <strong>Izinkan</strong>.</li>
            <li>Tombol berubah menjadi <span class="kbd">Notifikasi Aktif</span> dan tidak bisa ditekan lagi. Itu tandanya berhasil.</li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">
            Notifikasi terdaftar <strong>per perangkat</strong>. Kalau Anda memakai dua HP, lakukan langkah ini
            di masing-masing HP.
        </p>

        <p class="text-[14px] leading-relaxed mt-3">Notifikasi yang akan Anda terima antara lain:</p>
        <ul class="list-disc ms-5 space-y-1 text-[14px] leading-relaxed mt-1">
            <li>Pengingat absen masuk dan absen pulang.</li>
            <li>Pengajuan cuti yang perlu Anda setujui, dan hasil pengajuan cuti Anda sendiri.</li>
            <li>Pemberitahuan sanksi dan tiket, sesuai peran Anda.</li>
        </ul>

        <x-panduan.gambar src="instalasi-notifikasi.png" caption="Tombol Aktifkan Notifikasi di halaman Profil" />
        <x-panduan.gambar src="instalasi-notif-aktif.png" caption="Setelah izin diberikan, tombol berubah menjadi Notifikasi Aktif dan tidak bisa ditekan lagi" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <p class="text-[14px] leading-relaxed">
            Kalau tombol notifikasi memunculkan pesan, artinya begini:
        </p>

        <div class="space-y-3 mt-3">
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Pasang aplikasi dulu”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Anda memakai iPhone dan aplikasi masih dibuka lewat tab browser.
                    Pasang dulu ke layar utama (lihat bagian di atas), lalu buka dari ikonnya, baru aktifkan notifikasi.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Notifikasi belum aktif di server”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    HP Anda sudah mendukung, tapi kunci notifikasi di server belum disiapkan.
                    Ini bukan masalah HP Anda — <strong>hubungi Admin Sistem</strong>.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Notifikasi tidak didukung”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Browser yang Anda pakai memang tidak mendukung notifikasi. Coba pakai Chrome (Android)
                    atau Safari (iPhone), dan pastikan aplikasi sudah dipasang ke layar utama.
                </p>
            </div>
        </div>

        <x-panduan.catatan tipe="awas">
            <strong>Tampilan terlihat aneh setelah aplikasi diperbarui?</strong>
            iPhone menyimpan tampilan lama dengan sangat awet. Hapus ikon NirwanaHRIS dari layar utama,
            lalu pasang ulang mengikuti langkah di atas. Data Anda tidak akan hilang — semuanya tersimpan di server.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Izin lokasi dan kamera <strong>tidak</strong> diminta di halaman ini. Keduanya baru diminta saat
            Anda pertama kali membuka layar absen — lihat bab <a href="{{ route('panduan.bab', 'absensi') }}"
            class="hover:underline" style="color:var(--brand-600)">Absen Masuk &amp; Pulang</a>.
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
