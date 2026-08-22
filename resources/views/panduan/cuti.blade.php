@php
    $bagian = [
        ['id' => 'jatah', 'judul' => 'Memahami Jatah Cuti'],
        ['id' => 'ajukan', 'judul' => 'Mengajukan Cuti'],
        ['id' => 'status', 'judul' => 'Memantau Status Pengajuan'],
        ['id' => 'rantai', 'judul' => 'Siapa yang Menyetujui'],
        ['id' => 'menyetujui', 'judul' => 'Menyetujui atau Menolak'],
        ['id' => 'hrd', 'judul' => 'Kelola Cuti (HRD)'],
        ['id' => 'surat', 'judul' => 'Surat Cuti & QR Verifikasi'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <x-panduan.peran :peran="['Karyawan']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Aplikasi memakai istilah <strong>Jatah</strong> untuk sisa hak cuti tahunan Anda. Jatah tampil di
            kartu Beranda dan di halaman <strong>Operasional → Cuti</strong>.
        </p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-3">
            <li>Jatah dasar <strong>12 hari</strong> per periode, ditambah atau dikurangi penyesuaian dari HRD bila ada.</li>
            <li>Jatah baru terbit setelah <strong>masa kerja Anda genap 1 tahun</strong>. Sebelum itu, cuti tahunan belum bisa diajukan — tetapi jenis cuti lain seperti sakit, melahirkan, dan izin tetap bisa.</li>
            <li>Periode jatah mengikuti <strong>siklus kontrak Anda</strong>, bukan tanggal 1 Januari.</li>
            <li>Pengajuan yang masih berjalan sudah <strong>menahan</strong> jatah walau belum disetujui — supaya Anda tidak mengajukan melebihi hak dua kali.</li>
        </ul>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <x-panduan.peran :peran="['Karyawan']" />
        <x-panduan.langkah>
            <li>Buka <strong>Operasional → Cuti</strong>, tekan tombol ajukan.</li>
            <li>Pilih <strong>jenis cuti</strong>. Daftar yang muncul sudah disaring sesuai hak Anda.</li>
            <li>Isi <strong>tanggal mulai</strong> dan <strong>tanggal selesai</strong>.</li>
            <li>Isi <strong>jumlah hari</strong> yang benar-benar diambil. Boleh lebih kecil dari rentang tanggal — misalnya rentang 5 hari tetapi hanya 3 hari yang dihitung cuti — tetapi tidak boleh lebih besar.</li>
            <li>Tulis <strong>alasan</strong>.</li>
            <li>Unggah <strong>lampiran</strong> bila diperlukan: JPG, PNG, WEBP, atau PDF, maksimal 5 MB.</li>
            <li>Bila unit Anda memakai sistem pengganti, pilih rekan yang akan <strong>menggantikan shift</strong> Anda.</li>
            <li>Kirim.</li>
        </x-panduan.langkah>

        <h3 class="text-[15px] font-bold mt-6 mb-2">Pengajuan yang akan ditolak sistem</h3>
        <div class="space-y-3">
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Tanggal selesai tidak boleh sebelum tanggal mulai.”</div>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Jumlah hari melebihi rentang tanggal (… hari).”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">Kecilkan jumlah hari, atau lebarkan rentang tanggalnya.</p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Cuti tahunan maksimal 6 hari per pengajuan.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">Batas per satu kali pengajuan. Untuk cuti lebih panjang, ajukan bertahap.</p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Melebihi saldo cuti tahunan (sisa … hari).”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">Sisa jatah tidak cukup. Angka yang disebut sudah memperhitungkan pengajuan Anda yang masih berjalan.</p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Tanggal mulai tidak boleh di masa lampau untuk jenis ini.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">Sebagian jenis cuti boleh diajukan mundur (misalnya sakit), sebagian tidak.</p>
            </div>
            <div class="card card-pad">
                <div class="font-bold text-[14px]">“Lampiran wajib untuk jenis cuti ini.”</div>
                <p class="text-[13.5px] mt-1 leading-relaxed">Misalnya surat keterangan dokter untuk cuti sakit.</p>
            </div>
        </div>

        {{-- SS: form ajukan cuti di HP --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <x-panduan.peran :peran="['Karyawan']" />
        <div class="grid-wrap mt-3">
            <table class="table">
                <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
                <tbody>
                    <tr><td><strong>Diajukan</strong></td><td>Baru dikirim, belum ada yang menindak.</td></tr>
                    <tr><td><strong>Diproses</strong></td><td>Sudah disetujui sebagian penyetuju, masih menunggu tahap berikutnya.</td></tr>
                    <tr><td><strong>Disetujui</strong></td><td>Seluruh tahap selesai. Jatah dipotong dan surat cuti bisa dicetak.</td></tr>
                    <tr><td><strong>Ditolak</strong></td><td>Ditolak di salah satu tahap. Alasannya tertulis di detail pengajuan.</td></tr>
                    <tr><td><strong>Dibatalkan</strong></td><td>Dibatalkan oleh Anda sebelum selesai, atau oleh HRD setelah disetujui.</td></tr>
                </tbody>
            </table>
        </div>
        <p class="text-[14px] leading-relaxed mt-3">
            Buka satu pengajuan untuk melihat riwayat tiap tahap: siapa penyetujunya, kapan, dan catatannya.
            Perubahan status juga masuk ke Notifikasi dan Riwayat Anda.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">
            Rantai penyetuju disusun otomatis dari <strong>struktur organisasi</strong> saat pengajuan dikirim —
            tidak dipilih manual oleh pemohon.
        </p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-3">
            <li><strong>Karyawan staf</strong> — naik dari atasan langsung ke atas: koordinator, lalu kepala bidang/bagian, lalu <strong>HRD</strong> sebagai tahap terakhir.</li>
            <li><strong>Kepala bidang/bagian ke atas</strong> — langsung ke <strong>HRD</strong>, tanpa melewati jenjang di bawahnya.</li>
            <li><strong>Pemegang role HRD sendiri</strong> — pengajuannya hanya ke <strong>Direktur</strong>.</li>
            <li><strong>Direktur</strong> tidak ikut alur cuti pegawai, dan tidak menjadi penyetuju cuti umum.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Tahapnya <strong>berurutan</strong>: tahap berikutnya baru bisa bertindak setelah tahap sebelumnya menyetujui.
        </p>

        <x-panduan.catatan tipe="awas">
            Yang dianggap HRD adalah pemilik <strong>role akun HRD</strong>, bukan orang yang jabatannya bernama
            “Kepegawaian”. Sistem memakai <strong>satu</strong> pemegang role HRD; bila role itu diberikan ke lebih
            dari satu orang, hanya satu yang akan masuk rantai. Pastikan role HRD dipegang tepat satu orang.
        </x-panduan.catatan>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <x-panduan.peran :peran="['Kepala Unit', 'HRD', 'Direktur']" />
        <x-panduan.langkah>
            <li>Buka <strong>Operasional → Persetujuan Cuti</strong>. Menu ini hanya muncul bila Anda punya bawahan, atau memegang role HRD.</li>
            <li>Daftar berisi pengajuan yang <strong>menunggu giliran Anda</strong>. Pengajuan yang masih di tahap orang lain belum akan muncul.</li>
            <li>Buka satu pengajuan: periksa jenis, tanggal, jumlah hari, alasan, lampiran, dan usulan penggantinya.</li>
            <li>Tekan setuju, atau tolak. <strong>Penolakan wajib disertai catatan</strong> — itu yang dibaca pemohon.</li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">Pesan yang mungkin muncul:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-1">
            <li><span class="kbd">Tahap ini bukan tahap aktif.</span> — tahap sebelumnya belum menyetujui.</li>
            <li><span class="kbd">Anda bukan approver tahap ini.</span> — giliran itu milik orang lain.</li>
            <li><span class="kbd">Jatah tak cukup: sisa … hari, diminta … hari.</span> — jatah pemohon berubah sejak pengajuan dibuat; diperiksa ulang di tahap akhir.</li>
        </ul>

        {{-- SS: layar persetujuan cuti --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[5]['id']" :judul="$bagian[5]['judul']">
        <x-panduan.peran :peran="['HRD']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Menu <strong>Operasional → Kelola Cuti</strong> berisi tiga bagian pengaturan.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Hari libur</h3>
        <p class="text-[14px] leading-relaxed">
            Daftar tanggal merah beserta namanya. Dipakai kalender cuti sebagai penanda.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Jenis cuti</h3>
        <p class="text-[14px] leading-relaxed">
            Mengatur nama jenis cuti, apakah <strong>memotong jatah</strong>, apakah <strong>wajib lampiran</strong>,
            dan apakah <strong>boleh diajukan mundur</strong>. Jenis yang tak dipakai lagi cukup dinonaktifkan.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Penyesuaian jatah</h3>
        <x-panduan.langkah>
            <li>Cari karyawannya.</li>
            <li>Pilih <strong>periode</strong> — hanya periode berjalan dan periode berikutnya yang boleh disesuaikan.</li>
            <li>Isi <strong>selisih</strong> hari: positif menambah, negatif mengurangi.</li>
            <li>Tulis <strong>alasan</strong> — ini yang menjadi jejak audit, jadi tulis jelas.</li>
        </x-panduan.langkah>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Membatalkan cuti yang sudah disetujui</h3>
        <p class="text-[14px] leading-relaxed">
            Hanya HRD yang bisa, dan hanya untuk cuti berstatus <strong>Disetujui</strong>, disertai alasan.
            Percobaan lain ditolak dengan pesan
            <span class="kbd">Hanya cuti berstatus disetujui yang bisa dibatalkan.</span>
        </p>

        <p class="text-[14px] leading-relaxed mt-4">
            Menu <strong>Laporan Cuti</strong> menyediakan rekap pengajuan dan rekap jatah seluruh karyawan,
            lengkap dengan ekspornya.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[6]['id']" :judul="$bagian[6]['judul']">
        <p class="text-[14px] leading-relaxed">
            Cuti yang sudah disetujui bisa dicetak sebagai <strong>surat cuti</strong> dari halaman detail pengajuan.
        </p>
        <p class="text-[14px] leading-relaxed mt-3">
            Surat memuat <strong>kode QR</strong> sebagai pengganti tanda tangan basah. Memindainya membuka halaman
            verifikasi yang menampilkan data asli dari sistem, sehingga penerima surat bisa memastikan
            suratnya benar dan belum diubah. Halaman verifikasi bisa dibuka <strong>tanpa login</strong>,
            tetapi tautannya bertanda tangan digital — QR palsu tidak akan terbuka.
        </p>
        <x-panduan.catatan tipe="info">
            Tiap pihak yang menyetujui punya QR-nya sendiri. Jadi satu surat bisa memuat beberapa QR sekaligus,
            masing-masing mewakili satu tanda tangan.
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
