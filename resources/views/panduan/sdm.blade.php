@php
    $bagian = [
        ['id' => 'karyawan', 'judul' => 'Mendata Karyawan'],
        ['id' => 'kontrak', 'judul' => 'Kontrak & Pengingatnya'],
        ['id' => 'dokumen', 'judul' => 'Dokumen Karyawan'],
        ['id' => 'organisasi', 'judul' => 'Struktur Organisasi'],
        ['id' => 'jabatan', 'judul' => 'Jabatan & Rute Pengawas'],
        ['id' => 'izin', 'judul' => 'Jenis Izin & Pengingat Masa Berlaku'],
        ['id' => 'laporan', 'judul' => 'Laporan Kepegawaian'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Kelompok menu <strong>SDM</strong> adalah sumber data utama seluruh aplikasi. Struktur organisasi
        yang Anda susun di sini <strong>menentukan siapa menyetujui cuti siapa</strong>, siapa boleh menyusun
        jadwal, dan siapa boleh mengusulkan sanksi. Karena itu perubahan di sini berdampak luas.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <x-panduan.langkah>
            <li>Buka <strong>SDM → Karyawan</strong>, tekan tambah.</li>
            <li>Isi <strong>NIP</strong> dan <strong>nama lengkap</strong>.</li>
            <li>Isi data pribadi: NIK, tempat &amp; tanggal lahir, jenis kelamin, agama, status nikah, pendidikan.</li>
            <li>Isi kontak: nomor HP, email, alamat. Bagian ini nanti bisa diperbarui sendiri oleh karyawan lewat halaman Profil.</li>
            <li>Pilih <strong>jabatan</strong> — inilah yang menempatkan dia di unit tertentu.</li>
            <li>Isi <strong>tanggal masuk</strong>.</li>
            <li>Isi kontrak pertamanya, lalu simpan.</li>
        </x-panduan.langkah>

        <x-panduan.catatan tipe="awas">
            <strong>Atasan dan kepala unit tidak diisi manual.</strong> Keduanya dihitung sendiri dari
            struktur organisasi dan jabatan. Kalau seseorang menyetujui cuti orang yang salah,
            yang perlu diperbaiki adalah <strong>jabatan dan strukturnya</strong>, bukan mencari kolom atasan —
            kolom itu memang tidak ada.
        </x-panduan.catatan>

        {{-- SS: daftar karyawan --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">Ada empat jenis kontrak:</p>
        <div class="grid-wrap mt-2">
            <table class="table">
                <thead><tr><th>Jenis</th><th>Tanggal berakhir</th><th>Pengingat muncul</th></tr></thead>
                <tbody>
                    <tr><td><strong>Percobaan unpaid</strong></td><td>Wajib diisi</td><td>3 hari sebelum berakhir</td></tr>
                    <tr><td><strong>Percobaan</strong></td><td>Wajib diisi</td><td>30 hari sebelum berakhir</td></tr>
                    <tr><td><strong>PKWT</strong></td><td>Wajib diisi</td><td>30 hari sebelum berakhir</td></tr>
                    <tr><td><strong>Tetap</strong></td><td>Dikosongkan</td><td>Tidak ada</td></tr>
                </tbody>
            </table>
        </div>
        <p class="text-[14px] leading-relaxed mt-3">
            Tanggal berakhir harus <strong>sesudah</strong> tanggal mulai. Kontrak baru ditambahkan sebagai
            kontrak berikutnya, bukan menimpa yang lama — riwayat kontrak tetap utuh.
        </p>
        <x-panduan.catatan tipe="info">
            Kontrak yang mendekati atau sudah melewati tanggal berakhir muncul di kartu <strong>Kepegawaian</strong>
            pada Beranda, dipisah antara <em>akan berakhir</em> dan <em>terlewat</em>.
            Siklus kontrak juga menentukan <strong>periode jatah cuti</strong> karyawan.
        </x-panduan.catatan>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <p class="text-[14px] leading-relaxed">
            Halaman detail karyawan menyimpan berkas kepegawaian — ijazah, SK, kontrak yang ditandatangani,
            dan sejenisnya. Berkas hanya bisa dibuka oleh yang berwenang mengelola data SDM;
            tautannya tidak bisa dibagikan ke orang luar.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">
            <strong>SDM → Organisasi</strong> menampilkan struktur sebagai pohon empat tingkat:
            <strong>Direktur</strong> → <strong>Bidang</strong> → <strong>Bagian</strong> → <strong>Unit</strong>.
        </p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-3">
            <li>Tambahkan unit baru di bawah induk yang tepat, beri <strong>nama</strong> dan <strong>tipe</strong>.</li>
            <li>Tetapkan <strong>kepala unit</strong> — bila orangnya belum terdaftar, tersedia jalur tambah cepat.</li>
            <li>Nyalakan atau matikan <span class="kbd">Pengganti</span> per unit, sesuai perlu tidaknya sistem pengganti shift di unit itu.</li>
        </ul>

        <x-panduan.catatan tipe="awas">
            <strong>Mengubah tipe unit</strong> (misalnya dari bagian menjadi bidang) bisa memunculkan baris
            jabatan pimpinan baru, karena tingkat pimpinannya ikut berubah. Ini kelemahan yang belum dibereskan.
            Hindari mengubah tipe unit yang sudah berisi orang; kalau memang perlu, koordinasikan dengan
            Admin Sistem dan periksa jabatannya sesudahnya.
        </x-panduan.catatan>

        {{-- SS: struktur organisasi --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <p class="text-[14px] leading-relaxed">Tiap unit punya dua macam jabatan:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-2">
            <li><strong>Jabatan pimpinan</strong> — tepat satu per unit. Anda hanya bisa <strong>mengganti namanya</strong>; tingkat dan unitnya terkunci, dan jabatan ini tidak pernah dibuat atau dihapus manual.</li>
            <li><strong>Jabatan staf</strong> — boleh dibuat sebanyak yang diperlukan, dan satu jabatan staf bisa dipegang banyak orang.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Nama jabatan pimpinan mengikuti nama unit secara otomatis <strong>selama belum pernah Anda ubah</strong>.
            Begitu Anda menamainya sendiri, nama itu dipertahankan walau nama unitnya berubah.
        </p>

        <h3 class="text-[15px] font-bold mt-6 mb-2">Rute pengawas (SPI dan sejenisnya)</h3>
        <p class="text-[14px] leading-relaxed">
            Pada jabatan pimpinan tersedia pilihan <strong>rute pengawas</strong>. Mengisinya membuat pemegang
            jabatan itu boleh mengusulkan sanksi untuk <strong>seluruh karyawan</strong>, bukan hanya bawahannya —
            berguna untuk jabatan pengawas seperti SPI yang unitnya memang tidak beranggota.
        </p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-2">
            <li><strong>Pengawas — usulan langsung ke HRD</strong>: melewati koordinator dan kepala bidang unit yang diusulkan.</li>
            <li><strong>Pengawas — usulan lewat atasan karyawan terkait</strong>: masuk dari garis komando karyawan yang diusulkan.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Kosongkan pilihan ini untuk jabatan biasa. Karena melekat pada jabatan, kewenangannya ikut berpindah
            sendiri saat pemegangnya berganti. Rincian alurnya ada di bab
            <a href="{{ route('panduan.bab', 'disiplin') }}" class="hover:underline" style="color:var(--brand-600)">Disiplin &amp; Surat Peringatan</a>.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[5]['id']" :judul="$bagian[5]['judul']">
        <p class="text-[14px] leading-relaxed">
            <strong>SDM → Jenis Izin</strong> mengatur jenis izin dan sertifikat yang masa berlakunya perlu
            dipantau — misalnya STR atau SIP.
        </p>
        <x-panduan.langkah>
            <li>Beri <strong>nama</strong> jenis izin.</li>
            <li>Isi <strong>ambang pengingat</strong> dalam hari, antara <strong>1</strong> dan <strong>365</strong>. Bawaannya <strong>90 hari</strong> sebelum berakhir.</li>
            <li>Aktifkan atau nonaktifkan sesuai kebutuhan.</li>
        </x-panduan.langkah>
        <p class="text-[14px] leading-relaxed mt-3">
            Izin milik karyawan diisi di halaman detail karyawan, dan tampil bagi yang bersangkutan di
            halaman Profil. Saat mendekati ambang, pengingat dikirim ke <strong>HRD</strong> dan
            <strong>karyawan yang bersangkutan</strong>, serta muncul di kartu Kepegawaian pada Beranda.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[6]['id']" :judul="$bagian[6]['judul']">
        <p class="text-[14px] leading-relaxed">
            Tersedia dua unduhan: <strong>daftar karyawan</strong> dan <strong>pengingat kontrak</strong>.
            Keduanya memakai data terkini saat diunduh.
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
