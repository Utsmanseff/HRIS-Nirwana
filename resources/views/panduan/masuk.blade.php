@php
    $bagian = [
        ['id' => 'dua-cara', 'judul' => 'Dua Cara Masuk'],
        ['id' => 'klaim', 'judul' => 'Klaim Akun: Menghubungkan Diri ke Data Karyawan'],
        ['id' => 'sandi', 'judul' => 'Membuat & Mengganti Kata Sandi'],
        ['id' => 'masalah', 'judul' => 'Pesan Galat dan Artinya'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Langkah pertama adalah bisa masuk. Untuk sekarang cukup buka
        <span class="kbd">presensi.rsunirwana.id</span> lewat browser HP Anda —
        <strong>belum perlu memasang apa pun</strong>. Pemasangan ke layar utama dan penyalaan
        notifikasi dibahas di bab berikutnya, karena tombolnya berada di halaman Profil
        yang baru bisa dibuka setelah Anda masuk.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <p class="text-[14px] leading-relaxed">
            Halaman masuk menyediakan dua jalan. Keduanya membuka akun yang sama.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">1. Masuk dengan Google</h3>
        <p class="text-[14px] leading-relaxed">
            Tekan tombol <span class="kbd">Masuk dengan Google</span>, lalu pilih akun Google Anda.
            Ini cara yang dipakai <strong>saat pertama kali</strong>, karena akun Anda belum tentu punya kata sandi.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">2. Masuk dengan NIP</h3>
        <x-panduan.langkah>
            <li>Isi kolom <strong>NIP</strong> dengan NIP kepegawaian Anda.</li>
            <li>Isi <strong>Kata sandi</strong>.</li>
            <li>Centang <strong>Ingat saya</strong> bila HP ini milik pribadi, supaya tidak perlu masuk berulang kali.</li>
            <li>Tekan <span class="kbd">Masuk dengan NIP</span>.</li>
        </x-panduan.langkah>

        <x-panduan.catatan tipe="info">
            Cara NIP hanya bisa dipakai <strong>setelah</strong> Anda membuat kata sandi sendiri di halaman Profil.
            Selama belum, masuklah dengan Google.
        </x-panduan.catatan>

        <x-panduan.gambar src="masuk-login.png" caption="Halaman masuk di HP" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">
            Akun Google Anda dan data kepegawaian Anda awalnya belum saling terhubung. Karena itu, pada login
            pertama aplikasi mengarahkan Anda ke halaman <strong>Klaim Akun</strong>, dan
            <strong>tidak akan melepas Anda ke halaman lain</strong> sebelum klaim selesai. Ini normal, bukan galat.
        </p>

        <x-panduan.langkah>
            <li>Ketik <strong>nama</strong>, <strong>NIP</strong>, atau <strong>NIK</strong> Anda di kolom pencarian. Minimal <strong>3 huruf</strong> — di bawah itu hasil tidak akan muncul.</li>
            <li>Cari nama Anda di daftar hasil (maksimal 10 baris ditampilkan; kalau tidak terlihat, ketik lebih lengkap).</li>
            <li>Tekan baris nama Anda untuk mengklaimnya.</li>
            <li>Anda langsung diantar ke <strong>Beranda</strong>. Klaim cukup dilakukan sekali seumur akun.</li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">Data karyawan hanya muncul di pencarian bila:</p>
        <ul class="list-disc ms-5 space-y-1 text-[14px] leading-relaxed mt-1">
            <li>statusnya <strong>Aktif</strong>, dan</li>
            <li><strong>belum</strong> diklaim oleh akun lain.</li>
        </ul>

        <x-panduan.catatan tipe="bahaya">
            Jangan mengklaim data milik orang lain. Setelah diklaim, data itu terkunci ke akun tersebut dan
            hanya Admin Sistem yang bisa melepasnya — sementara absensi, cuti, dan sanksi Anda akan tercatat
            atas nama orang yang salah.
        </x-panduan.catatan>

        <x-panduan.gambar src="masuk-klaim.png" caption="Halaman klaim akun: ketik nama, NIP, atau NIK, lalu tekan Pilih" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <p class="text-[14px] leading-relaxed">
            Kata sandi dibuat dan diganti di halaman <strong>Profil</strong>.
        </p>

        <x-panduan.langkah>
            <li>Buka <strong>Profil</strong>.</li>
            <li>Cari bagian kata sandi.</li>
            <li><strong>Kalau Anda belum pernah punya kata sandi</strong> (masuk lewat Google), kolom kata sandi lama tidak perlu diisi.</li>
            <li><strong>Kalau sudah punya</strong>, isi kata sandi lama lebih dulu.</li>
            <li>Isi kata sandi baru — <strong>minimal 8 karakter</strong> — lalu ulangi di kolom konfirmasi. Keduanya harus sama persis.</li>
            <li>Simpan. Muncul pesan <span class="kbd">Kata sandi disimpan.</span></li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">
            Setelah itu Anda bisa masuk lewat NIP + kata sandi, tanpa Google. Cara Google tetap bisa dipakai.
        </p>

        <x-panduan.catatan tipe="info">
            <strong>Lupa kata sandi?</strong> Aplikasi belum punya fitur reset lewat email.
            Masuklah dengan Google, lalu buat kata sandi baru di Profil — kata sandi lama tidak perlu diisi
            hanya jika akun Anda memang belum punya kata sandi. Kalau sudah punya dan benar-benar lupa,
            hubungi Admin Sistem.
        </x-panduan.catatan>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <div class="space-y-3">
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“NIP atau kata sandi salah.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    NIP tidak ditemukan, kata sandi keliru, atau akun Anda memang belum punya kata sandi.
                    Periksa NIP Anda, atau masuk lewat Google.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Akun dinonaktifkan. Hubungi admin sistem.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Akun Anda dinonaktifkan. Ini juga bisa muncul <strong>di tengah pemakaian</strong> —
                    kalau akun dinonaktifkan saat Anda sedang masuk, sesi langsung diputus dan Anda dikembalikan
                    ke halaman masuk. Hubungi Admin Sistem.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Gagal masuk dengan Google.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Proses Google terputus — biasanya karena koneksi atau karena Anda membatalkan di layar Google.
                    Coba lagi.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Data ini sudah terhubung ke akun lain. Hubungi admin.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Muncul di halaman klaim: data karyawan yang Anda pilih sudah diklaim orang lain,
                    atau statusnya bukan Aktif. Hubungi Admin Sistem.
                </p>
            </div>
        </div>

        <x-panduan.catatan tipe="info">
            Sudah berhasil masuk? Lanjut ke bab
            <a href="{{ route('panduan.bab', 'instalasi') }}" class="hover:underline" style="color:var(--brand-600)">Memasang Aplikasi di HP</a>
            untuk memasang NirwanaHRIS ke layar utama dan menyalakan notifikasi.
        </x-panduan.catatan>
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
