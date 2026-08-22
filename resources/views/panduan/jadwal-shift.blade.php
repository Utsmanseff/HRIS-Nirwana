@php
    $bagian = [
        ['id' => 'siapa', 'judul' => 'Siapa yang Bisa Menyusun Jadwal'],
        ['id' => 'shift', 'judul' => 'Langkah 1 — Membuat Shift'],
        ['id' => 'pola', 'judul' => 'Langkah 2 — Membuat Pola Bernama'],
        ['id' => 'anggota', 'judul' => 'Mengatur Anggota Pola'],
        ['id' => 'terapkan', 'judul' => 'Langkah 3 — Menerapkan Pola ke Bulan'],
        ['id' => 'manual', 'judul' => 'Mengubah Jadwal Satu Per Satu'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Menu <strong>Operasional → Jadwal Shift</strong> punya tiga tab yang dipakai berurutan:
        <strong>Shift</strong> (jenis dinas), <strong>Pola</strong> (rangkaian shift yang berulang), dan
        <strong>Jadwal Bulanan</strong> (kalender nyata yang dipakai absensi).
        Susun sekali di awal, bulan-bulan berikutnya tinggal menerapkan pola.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <p class="text-[14px] leading-relaxed">
            Menu ini hanya muncul untuk <strong>pemegang jabatan pimpinan</strong> — koordinator, kepala bagian,
            kepala bidang, dan seterusnya. Karyawan staf tidak melihatnya. Direktur juga tidak, karena tidak
            ikut alur penjadwalan pegawai.
        </p>
        <p class="text-[14px] leading-relaxed mt-3">
            Kalau Anda memimpin lebih dari satu unit, ada pemilih unit di bagian atas. Semua yang Anda buat —
            shift, pola, jadwal — <strong>melekat pada unit yang sedang dipilih</strong>. Pastikan unitnya benar
            sebelum mulai.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">
            Shift adalah jenis dinas, misalnya Pagi 07.00–14.00. Buat semua shift yang dipakai unit Anda lebih dulu.
        </p>
        <x-panduan.langkah>
            <li>Buka tab <strong>Shift</strong>.</li>
            <li>Isi <strong>Nama</strong> shift, misalnya “Pagi”.</li>
            <li>Isi <strong>Kode</strong> — <strong>maksimal 4 huruf</strong> dan harus unik di unit Anda, misalnya <span class="kbd">P</span>, <span class="kbd">S</span>, <span class="kbd">M</span>. Kode inilah yang nanti Anda ketik di grid, jadi buat sesingkat mungkin. Huruf kecil otomatis dijadikan huruf besar.</li>
            <li>Pilih <strong>warna</strong> — hanya untuk membedakan secara visual di grid.</li>
            <li>Isi <strong>jam mulai</strong> dan <strong>jam selesai</strong>. Shift yang melewati tengah malam (misal 21.00–07.00) boleh, tulis apa adanya.</li>
            <li>Isi <strong>toleransi telat</strong> dalam menit, antara <strong>0</strong> dan <strong>120</strong>.</li>
            <li>Simpan.</li>
        </x-panduan.langkah>

        <x-panduan.catatan tipe="awas">
            Toleransi hanya menentukan kapan seseorang <strong>dicap telat</strong>. Toleransi
            <strong>tidak</strong> mengurangi kewajiban jam kerja — orang yang masuk 5 menit lewat masih harus
            bekerja selama panjang shift, kalau tidak akan muncul sebagai pulang cepat. Ini disengaja.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Shift yang tak dipakai lagi sebaiknya <strong>dinonaktifkan</strong>, bukan dihapus —
            jadwal dan absensi lama yang memakainya tetap utuh.
        </p>

        {{-- SS: tab Shift --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <p class="text-[14px] leading-relaxed">
            Pola adalah <strong>rangkaian shift yang berulang</strong>, diberi nama supaya mudah dikenali —
            misalnya “Rotasi 3 Regu” atau “Jaga Malam”. Satu unit boleh punya beberapa pola sekaligus.
        </p>

        <x-panduan.langkah>
            <li>Buka tab <strong>Pola</strong>, tekan tombol buat pola.</li>
            <li>Beri <strong>nama</strong> pola (maksimal 60 huruf). Nama harus <strong>unik di unit Anda</strong>.</li>
            <li>Pilih <strong>mode</strong>:
                <ul class="list-disc ms-5 mt-1 space-y-1">
                    <li><strong>Rotasi</strong> — siklus berputar terus tanpa peduli nama hari. Cocok untuk pola jaga seperti P–P–S–S–M–M–L.</li>
                    <li><strong>Mingguan</strong> — jam tetap menurut nama hari. Barisnya selalu 7 kolom, urut <strong>Senin sampai Minggu</strong>. Cocok untuk unit kerja kantoran.</li>
                </ul>
            </li>
            <li>Isi <strong>tanggal jangkar</strong>: tanggal yang dianggap sebagai kolom pertama siklus. Ini yang menentukan “hari ini regu siapa”. (Hanya berpengaruh pada mode rotasi.)</li>
            <li>Isi <strong>grid</strong>: satu baris per karyawan, satu kolom per posisi siklus. Ketik kode shift di tiap kotak. Kosong atau <span class="kbd">L</span> berarti <strong>libur</strong>.</li>
            <li>Simpan. Muncul pesan <span class="kbd">Pola disimpan.</span></li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">
            Pada mode rotasi, tiap baris boleh punya <strong>panjang siklus berbeda</strong> — gunakan tombol tambah/kurang
            kolom pada baris itu. Pada mode mingguan panjangnya selalu 7 dan tidak bisa diubah.
        </p>

        <x-panduan.catatan tipe="awas">
            Kalau Anda mengetik kode yang belum ada di unit itu, penyimpanan ditolak dengan pesan
            <span class="kbd">Kode "…" tidak dikenal di unit ini.</span> Buat shift-nya dulu di tab Shift.
        </x-panduan.catatan>

        {{-- SS: tab Pola dengan grid --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">
            Tambahkan karyawan ke pola lewat kotak pencarian anggota. Aturan pentingnya:
        </p>

        <x-panduan.catatan tipe="info">
            <strong>Satu karyawan hanya boleh ada di satu pola per unit.</strong> Kalau Anda menambahkan orang
            yang sudah terdaftar di pola lain, dia otomatis dikeluarkan dari pola lamanya saat Anda menyimpan.
            Itu bukan kesalahan — memang begitu aturannya, supaya jadwalnya tidak dihasilkan dua kali.
        </x-panduan.catatan>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Menukar dua orang</h3>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed">
            <li><strong>Dalam pola yang sama</strong> — tukar posisi baris keduanya, misalnya karena regu mereka bertukar.</li>
            <li><strong>Antar pola berbeda</strong> — pilih orang dari pola lain, lalu pilih lawan tukarnya di pola yang sedang dibuka. Muncul pesan <span class="kbd">Dua karyawan ditukar antar pola.</span></li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Pada tukar antar pola, <strong>siklusnya tetap di tempat</strong> — yang berpindah hanya orangnya.
            Jadi kalau Ani ditukar dengan Budi, Ani langsung memakai siklus yang dulu dipegang Budi, dan sebaliknya.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Menghapus pola</h3>
        <p class="text-[14px] leading-relaxed">
            Menghapus pola hanya membuang cetakannya. <strong>Jadwal bulanan yang sudah terbentuk tidak ikut terhapus</strong>,
            karena itu sudah menjadi data nyata yang dipakai absensi.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <p class="text-[14px] leading-relaxed">
            Pola belum berarti apa-apa sampai diterapkan ke sebuah bulan.
        </p>
        <x-panduan.langkah>
            <li>Buka tab <strong>Jadwal Bulanan</strong>.</li>
            <li>Pastikan bulan yang tampil sudah benar — gunakan tombol panah untuk berpindah bulan.</li>
            <li>Tekan tombol terapkan pada pola yang dikehendaki.</li>
            <li>Kalender terisi otomatis untuk seluruh anggota pola itu.</li>
        </x-panduan.langkah>

        <x-panduan.catatan tipe="bahaya">
            <strong>Menerapkan pola akan MENIMPA seluruh bulan itu untuk anggota pola tersebut.</strong>
            Semua perubahan manual yang sudah Anda buat pada bulan itu — termasuk dinas ganda yang diketik tangan —
            akan hilang dan digantikan isi pola. Terapkan pola <strong>dulu</strong>, baru rapikan manual;
            jangan sebaliknya.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Anggota pola lain, dan karyawan yang tidak masuk pola mana pun, tidak tersentuh.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[5]['id']" :judul="$bagian[5]['judul']">
        <p class="text-[14px] leading-relaxed">
            Di tab Jadwal Bulanan, tiap kotak kalender bisa langsung diketik:
        </p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-2">
            <li>Ketik <strong>satu kode shift</strong>, misalnya <span class="kbd">P</span>.</li>
            <li>Untuk <strong>dinas ganda</strong>, tulis dua kode dipisah koma atau tanda tambah: <span class="kbd">P,S</span> atau <span class="kbd">P+S</span>.</li>
            <li><strong>Kosongkan</strong> kotaknya (atau tulis <span class="kbd">L</span>) untuk menghapus jadwal hari itu.</li>
        </ul>

        <p class="text-[14px] leading-relaxed mt-3">Yang akan ditolak:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-1">
            <li><span class="kbd">Kode "…" tidak dikenal.</span> — shift itu belum ada di unit ini.</li>
            <li><span class="kbd">Shift X dan Y bentrok jamnya.</span> — dua shift yang jamnya beririsan tidak boleh di hari yang sama. Bersentuhan di ujung, misalnya 16.00–00.00 lalu 00.00–08.00, <strong>bukan</strong> bentrok dan tetap boleh.</li>
        </ul>

        <p class="text-[14px] leading-relaxed mt-4">
            Anda hanya bisa mengubah jadwal karyawan yang berada di bawah kelolaan Anda. Mencoba mengubah
            milik unit lain akan ditolak.
        </p>

        <p class="text-[14px] leading-relaxed mt-3">
            Mengisi jadwal juga otomatis memperbarui daftar lowongan pengganti — lihat bab
            <a href="{{ route('panduan.bab', 'pengganti') }}" class="hover:underline" style="color:var(--brand-600)">Pengganti Jadwal</a>.
        </p>

        {{-- SS: grid jadwal bulanan --}}
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
