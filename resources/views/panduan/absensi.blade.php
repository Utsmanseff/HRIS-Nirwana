@php
    $bagian = [
        ['id' => 'sebelum', 'judul' => 'Sebelum Absen: Izin Kamera & Lokasi'],
        ['id' => 'masuk', 'judul' => 'Absen Masuk'],
        ['id' => 'pulang', 'judul' => 'Absen Pulang'],
        ['id' => 'status', 'judul' => 'Arti Status: Normal, Telat, Pulang Cepat, Anomali'],
        ['id' => 'masalah', 'judul' => 'Kalau Tombol Absen Tidak Bisa Ditekan'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Absen dilakukan dari HP Anda sendiri: aplikasi memeriksa <strong>posisi</strong> Anda,
        memotret <strong>wajah</strong> Anda, lalu mencatat jamnya. Satu hari kerja terdiri dari dua langkah —
        <strong>Absen Masuk</strong>, lalu <strong>Absen Pulang</strong>.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <p class="text-[14px] leading-relaxed">
            Saat pertama kali membuka layar Absensi, HP akan meminta dua izin. Keduanya
            <strong>wajib</strong> — tanpa itu tombol absen tidak akan bisa ditekan.
        </p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-2">
            <li><strong>Lokasi</strong> — untuk memastikan Anda benar-benar berada di area rumah sakit.</li>
            <li><strong>Kamera</strong> — untuk foto bukti kehadiran.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Pilih <strong>Izinkan</strong> pada keduanya. Kalau tanpa sengaja menekan Tolak, izin itu harus
            dipulihkan lewat pengaturan browser/HP — tutup dan buka ulang halaman saja tidak cukup.
        </p>

        <x-panduan.catatan tipe="info">
            Tombol absen baru menyala kalau <strong>semua</strong> syarat ini terpenuhi sekaligus:
            kamera aktif, wajah terdeteksi di layar, posisi Anda di dalam radius kantor,
            dan ketelitian GPS cukup baik. Selama salah satu belum terpenuhi, tombol sengaja dimatikan
            supaya Anda tidak mengira sudah absen padahal belum.
        </x-panduan.catatan>

        <x-panduan.gambar src="absensi-swipe.png" caption="Layar absen: chip shift hari ini, bingkai wajah, keterangan lokasi, lalu tombol absen" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <x-panduan.langkah>
            <li>Buka <strong>Beranda</strong> → kartu <strong>Absensi</strong>, atau menu <strong>Operasional → Absensi</strong>.</li>
            <li>Tunggu tulisan status di atas kamera berubah menjadi <span class="kbd">Wajah terdeteksi</span> (hijau). Kalau masih <span class="kbd">Menyiapkan deteksi wajah…</span>, tunggu sebentar — aplikasi sedang memuat pendeteksinya.</li>
            <li>Pastikan keterangan lokasi menunjukkan Anda berada di dalam radius. Peta di bawah tombol membantu: <strong>titik hijau</strong> = kantor beserta radiusnya, <strong>titik biru</strong> = posisi Anda.</li>
            <li>Tekan <span class="kbd">Absen Masuk</span>. Tombol berubah jadi <span class="kbd">Mengirim…</span>.</li>
            <li>Muncul pesan <span class="kbd">Absensi tercatat.</span> Selesai.</li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">
            Saat itu juga aplikasi menyimpan potret shift Anda hari itu (nama shift, jam mulai, jam selesai,
            toleransi) dan menghitung keterlambatan. Kalau hari itu Anda punya <strong>dua shift</strong> (dinas ganda),
            aplikasi memilih shift yang paling dekat dengan jam absen Anda dan belum terpakai — bukan asal shift pertama.
        </p>

        <p class="text-[13.5px] leading-relaxed mt-3" style="color:var(--text-muted)">
            Peta memerlukan internet untuk menggambar petanya. Kalau peta gagal tampil, pemeriksaan radius
            tetap berjalan normal — absen Anda tidak terganggu.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <p class="text-[14px] leading-relaxed">
            Saat pulang, buka layar yang sama. Tombolnya sudah otomatis berubah menjadi
            <span class="kbd">Absen Pulang</span>, karena aplikasi tahu sesi Anda masih terbuka.
            Langkahnya sama: wajah terdeteksi, di dalam radius, tekan tombol.
        </p>

        <x-panduan.catatan tipe="bahaya">
            <strong>Jangan menekan Absen Pulang tepat setelah Absen Masuk.</strong>
            Sesi yang panjangnya kurang dari <strong>5 menit</strong> ditandai <strong>Anomali</strong> di laporan
            dan akan ditanyakan atasan. Ini kesalahan yang paling sering terjadi: layar ditekan dua kali
            karena dikira absennya belum masuk.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Selama Anda belum absen pulang, sesi dianggap masih berjalan dan Anda
            <strong>tidak bisa</strong> memulai sesi baru. Bila Anda mencoba, muncul pesan
            <span class="kbd">Masih ada sesi aktif — absen pulang dulu.</span>
            Sebaliknya, menekan pulang tanpa pernah absen masuk memunculkan
            <span class="kbd">Tidak ada sesi aktif — absen masuk dulu.</span>
        </p>

        <p class="text-[14px] leading-relaxed mt-3">
            Di bawah peta ada <strong>Riwayat 7 Hari</strong> — sekilas jam masuk, jam pulang, dan status
            absen Anda sepekan terakhir.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <div class="grid-wrap">
            <table class="table">
                <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge badge-success">Normal</span></td><td>Ada shift terjadwal, tidak telat, jam kerja terpenuhi.</td></tr>
                    <tr><td><span class="badge badge-success">Tercatat</span></td><td>Absen tersimpan, tetapi hari itu Anda memang tidak punya shift terjadwal — jadi tidak ada patokan untuk menilai telat atau pulang cepat.</td></tr>
                    <tr><td><span class="badge badge-warning">Telat</span></td><td>Jam masuk melewati jam mulai shift <strong>ditambah</strong> toleransi. Angkanya dihitung dari jam mulai shift, bukan dari batas toleransi.</td></tr>
                    <tr><td><span class="badge badge-warning">Pulang cepat</span></td><td>Jam kerja Anda kurang dari panjang shift. Lihat penjelasan di bawah — ini bukan sekadar “pulang sebelum jam selesai”.</td></tr>
                    <tr><td><span class="badge badge-danger">Anomali</span></td><td>Durasinya tidak masuk akal: kurang dari 5 menit, atau lebih dari 16 jam.</td></tr>
                </tbody>
            </table>
        </div>

        <h3 class="text-[15px] font-bold mt-6 mb-2">Pulang cepat dihitung dari jam masuk Anda</h3>
        <p class="text-[14px] leading-relaxed">
            Kewajiban Anda adalah <strong>memenuhi panjang shift</strong>, bukan menunggu sampai jam selesai
            shift lewat. Rumusnya:
        </p>
        <p class="text-[14px] leading-relaxed mt-2 card card-pad">
            <strong>pulang cepat = panjang shift − lama Anda bekerja</strong> (dihitung dari jam masuk Anda yang sebenarnya)
        </p>
        <p class="text-[14px] leading-relaxed mt-3">Akibatnya:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-1">
            <li><strong>Datang lebih awal, boleh pulang lebih awal.</strong> Shift 09.00–16.00, Anda masuk 08.00 dan pulang 15.00 — jam kerja tetap 7 jam, tidak dihitung pulang cepat.</li>
            <li><strong>Datang telat, jam pulang ikut mundur.</strong> Masuk 09.30 pada shift yang sama berarti jam pulang Anda menjadi 16.30.</li>
            <li><strong>Toleransi tidak memotong kewajiban jam kerja.</strong> Telat 5 menit yang masih dalam toleransi tidak dicap “Telat”, tetapi tetap mengurangi jam kerja — kalau Anda pulang sesuai jam selesai shift, kekurangan 5 menit itu muncul sebagai pulang cepat. Ini disengaja: toleransi mengatur label kedisiplinan, bukan lamanya bekerja.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Aturan ini juga berlaku wajar untuk <strong>shift malam</strong> yang melewati tengah malam.
        </p>

        <p class="text-[14px] leading-relaxed mt-4">
            Lama telat dan pulang cepat ditampilkan sebagai <span class="kbd">2j 15m</span>, <span class="kbd">1j</span>,
            atau <span class="kbd">45m</span>.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <div class="space-y-3">
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Di luar radius kantor — absen ditolak.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Posisi Anda di luar batas area yang ditetapkan. Masuk lebih ke dalam area rumah sakit lalu coba lagi.
                    Batas radius diatur Admin Sistem — kalau Anda yakin sudah berada di dalam area tetapi tetap ditolak,
                    laporkan agar titik dan radiusnya diperiksa.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Akurasi lokasi terlalu buruk — coba lagi di tempat terbuka.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Sinyal GPS lemah, biasanya karena berada di dalam gedung berdinding tebal atau di basement.
                    Keluar ke tempat yang lebih terbuka, tunggu beberapa detik sampai keterangan lokasi membaik, lalu ulangi.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">Status wajah tetap “Wajah tak terdeteksi”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Perbaiki pencahayaan, dekatkan wajah ke bingkai, lepas masker, dan jangan membelakangi cahaya terang.
                </p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">Kamera tidak muncul sama sekali</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">
                    Izin kamera tertolak, atau kamera sedang dipakai aplikasi lain. Tutup aplikasi kamera lain,
                    lalu periksa izin situs di pengaturan browser/HP.
                </p>
            </div>
        </div>

        <x-panduan.catatan tipe="info">
            Absen yang <strong>gagal</strong> tidak tersimpan sama sekali — tidak ada catatan setengah jadi.
            Kalau Anda benar-benar tidak bisa absen karena kendala teknis, laporkan ke atasan langsung dan
            bagian kepegawaian pada hari itu juga, jangan menunggu akhir bulan.
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
