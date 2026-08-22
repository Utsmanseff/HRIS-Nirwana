@php
    $bagian = [
        ['id' => 'kenapa', 'judul' => 'Kenapa Harus Lewat Tiket'],
        ['id' => 'melapor', 'judul' => 'Melaporkan Kerusakan'],
        ['id' => 'memantau', 'judul' => 'Memantau Tiket Anda'],
        ['id' => 'mengerjakan', 'judul' => 'Mengerjakan Tiket (Tim Teknis)'],
        ['id' => 'laporan', 'judul' => 'Laporan Tiket'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Tiket dipakai melaporkan kerusakan atau permintaan perbaikan ke tiga tim:
        <strong>IT</strong>, <strong>Sarana</strong>, dan <strong>ATEM</strong> (alat kesehatan).
        Menu <strong>Tiket</strong> terlihat oleh <strong>semua karyawan</strong> tanpa kecuali.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <p class="text-[14px] leading-relaxed">
            Melapor lewat tiket memang terasa lebih lama daripada menelepon atau menitip pesan.
            Bedanya, tiket <strong>meninggalkan jejak</strong> — dan jejak itulah yang dipakai
            rumah sakit untuk memperbaiki keadaan, bukan sekadar memadamkan masalah satu per satu.
        </p>

        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-3">
            <li><strong>Transparan.</strong> Anda bisa melihat sendiri tiket sudah dibaca, sedang dikerjakan, atau selesai — tanpa perlu menanyakan ulang. Tim teknis juga tidak perlu mengingat-ingat siapa melapor apa.</li>
            <li><strong>Tercatat.</strong> Tiap laporan punya nomor, waktu lapor, siapa yang menangani, dan catatan penyelesaiannya. Laporan lisan mudah terlupa saat tim sedang menangani hal lain; tiket tidak.</li>
            <li><strong>Antre dengan adil.</strong> Yang masuk lebih dulu dan yang lebih mendesak terlihat jelas, jadi urusan tidak didahulukan hanya karena pelapornya paling sering menagih.</li>
            <li><strong>Jadi bahan evaluasi.</strong> Kerusakan yang berulang di alat atau ruangan yang sama akan kelihatan polanya dari rekapnya. Itu dasar yang kuat untuk mengusulkan perbaikan menyeluruh, penggantian alat, atau anggaran pemeliharaan — jauh lebih meyakinkan daripada mengandalkan ingatan.</li>
            <li><strong>Menempel ke asetnya.</strong> Tiket yang ditautkan ke aset inventaris membuat riwayat kerusakan alat itu terkumpul di satu tempat, sehingga terlihat mana alat yang sudah terlalu sering rusak.</li>
        </ul>

        <p class="text-[14px] leading-relaxed mt-4">
            Karena itu, biasakan tetap membuat tiket <strong>walaupun</strong> masalahnya sudah selesai
            ditangani lewat jalan lain. Yang hilang dari catatan, hilang juga dari perencanaan.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <x-panduan.peran :peran="['Semua']" />
        <x-panduan.langkah>
            <li>Buka menu <strong>Tiket</strong>, tekan tombol buat tiket.</li>
            <li>Pilih <strong>tim</strong> tujuan: IT, Sarana, atau ATEM. Salah pilih tim membuat tiket menunggu di antrian yang salah — jadi pastikan dulu.</li>
            <li>Pilih <strong>prioritas</strong>: Rendah, Sedang, Tinggi, atau Urgent. Pakai <strong>Urgent</strong> hanya untuk hal yang benar-benar menghentikan pelayanan.</li>
            <li>Isi <strong>judul</strong> singkat, maksimal 150 huruf.</li>
            <li>Isi <strong>deskripsi</strong>: apa yang rusak, di ruang mana, sejak kapan, dan apa yang sudah dicoba.</li>
            <li>Kirim. Tiket mendapat nomor otomatis berbentuk <span class="kbd">TKT-2026-0001</span>.</li>
        </x-panduan.langkah>

        <x-panduan.catatan tipe="info">
            Bagi karyawan biasa, aplikasi mengisi sendiri beberapa hal: pelapor adalah <strong>Anda</strong>,
            jenisnya <strong>Perbaikan</strong>, waktu lapor adalah <strong>sekarang</strong>, dan statusnya
            <strong>Baru</strong>. Kolom-kolom itu hanya bisa diatur anggota tim teknis.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Setelah tiket dibuat, Anda bisa menambahkan <strong>lampiran</strong> di halaman detail tiket —
            foto kerusakan sangat membantu. Format JPG, PNG, WEBP, atau PDF, maksimal 8 MB per berkas.
        </p>

        <x-panduan.catatan tipe="awas">
            <strong>Keadaan darurat: hubungi tim teknis langsung, jangan menunggu tiket dibaca.</strong>
            Kalau kerusakan menghentikan pelayanan atau membahayakan pasien — listrik ruang tindakan padam,
            alat kesehatan mati saat dipakai, jaringan mati total — telepon atau datangi tim terkait saat itu juga.
            Tiket bukan nomor darurat: petugas baru melihatnya saat membuka aplikasi.
            <br><br>
            Tiketnya <strong>tetap dibuat sesudahnya</strong>, oleh Anda atau oleh tim teknis atas nama Anda,
            supaya kejadian itu tetap tercatat dan ikut terhitung dalam evaluasi. Menangani tanpa mencatat
            membuat kerusakan yang sama terlihat “tidak pernah terjadi”.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="info">
            Sebelum melapor, tengok dulu daftar tiket — bisa jadi rekan seruangan sudah melaporkannya.
            Laporan ganda membuat antrean terlihat lebih panjang daripada kenyataannya.
            Dan pakailah <strong>Urgent</strong> seperlunya: kalau semua tiket ditandai urgent,
            tandanya berhenti berarti apa-apa.
        </x-panduan.catatan>

        <x-panduan.gambar src="tiket-buat.png" caption="Form pembuatan tiket: pilih tim tujuan, prioritas, judul, dan uraian kerusakan" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <div class="grid-wrap">
            <table class="table">
                <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge badge-info">Baru</span></td><td>Sudah masuk antrian, belum ada yang mengambil.</td></tr>
                    <tr><td><span class="badge badge-warning">Diproses</span></td><td>Sedang dikerjakan tim teknis.</td></tr>
                    <tr><td><span class="badge badge-success">Selesai</span></td><td>Sudah selesai, biasanya disertai catatan penyelesaian.</td></tr>
                    <tr><td><span class="badge badge-neutral">Batal</span></td><td>Dibatalkan, misalnya laporan ganda atau ternyata tidak rusak.</td></tr>
                </tbody>
            </table>
        </div>
        <p class="text-[14px] leading-relaxed mt-3">
            Kartu <strong>Tiket Saya</strong> di Beranda menampilkan jumlah tiket yang Anda laporkan.
            Perkembangannya juga tercatat di <strong>Riwayat</strong> Anda.
        </p>

        <x-panduan.gambar src="tiket-daftar.png" caption="Daftar tiket: ① badge status menunjukkan tiket sedang dikerjakan" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <x-panduan.peran :peran="['Tim Teknis']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Anggota tim teknis melihat <strong>antrian tim</strong>-nya di Beranda dan bisa menangani tiket.
            Kewenangan ini datang dari role akun: <strong>IT</strong>, <strong>Teknisi</strong> (Sarana), atau
            <strong>ATEM</strong>. Satu orang bisa memegang lebih dari satu tim.
        </p>

        <x-panduan.langkah>
            <li>Buka tiket dari antrian.</li>
            <li>Tekan mulai kerjakan — status berubah <strong>Baru → Diproses</strong>. Tiket yang statusnya bukan Baru akan ditolak: <span class="kbd">Tiket hanya bisa diproses dari status Baru.</span></li>
            <li>Setelah rampung, tandai selesai dan tulis <strong>catatan penyelesaian</strong>. Tiket yang sudah tidak aktif ditolak: <span class="kbd">Tiket sudah tidak aktif, tak bisa diselesaikan.</span></li>
            <li>Bila perlu dibatalkan, gunakan batalkan — hanya berlaku untuk tiket yang masih aktif: <span class="kbd">Hanya tiket aktif yang bisa dibatalkan.</span></li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">Tambahan yang hanya bisa dilakukan tim teknis:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-1">
            <li>Membuat tiket <strong>atas nama pelapor lain</strong> — berguna untuk laporan yang masuk lewat telepon.</li>
            <li>Mengubah <strong>waktu lapor</strong> agar sesuai kejadian sebenarnya.</li>
            <li>Memilih jenis <strong>Pemeliharaan</strong>, bukan hanya Perbaikan.</li>
            <li>Menautkan tiket ke <strong>aset inventaris</strong>, sehingga riwayat kerusakan aset itu terkumpul.</li>
            <li>Langsung membuat tiket berstatus Diproses atau Selesai, untuk pekerjaan yang sudah telanjur dikerjakan.</li>
        </ul>

        <x-panduan.gambar src="tiket-detail.png" caption="Detail tiket berisi uraian, lampiran, dan tombol penanganan" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <x-panduan.peran :peran="['Tim Teknis']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Menu <strong>Operasional → Tiket → Laporan</strong> merekap tiket berdasarkan tim, status,
            prioritas, dan rentang waktu, lengkap dengan ekspornya.
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
