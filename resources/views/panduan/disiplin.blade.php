@php
    $bagian = [
        ['id' => 'sanksi-saya', 'judul' => 'Melihat Sanksi Milik Sendiri'],
        ['id' => 'mengusulkan', 'judul' => 'Mengusulkan Sanksi'],
        ['id' => 'pengawas', 'judul' => 'Jabatan Pengawas (SPI) — Usulan Lintas Unit'],
        ['id' => 'rantai', 'judul' => 'Rantai Persetujuan'],
        ['id' => 'surat', 'judul' => 'Surat SP & QR Verifikasi'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Modul disiplin menangani teguran dan surat peringatan. Alurnya: seseorang <strong>mengusulkan</strong>,
        beberapa pihak <strong>menyetujui</strong> berurutan, dan tahap terakhir — Direktur —
        <strong>menerbitkan</strong>. Tidak ada yang bisa menerbitkan sanksi sendirian, kecuali Direktur.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <x-panduan.peran :peran="['Karyawan']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Menu <strong>Operasional → Sanksi Saya</strong> memperlihatkan sanksi atas nama Anda sendiri
            beserta tingkat, uraian, tanggal kejadian, dan masa berlakunya. Sanksi orang lain tidak terlihat.
        </p>
        <p class="text-[14px] leading-relaxed mt-3">
            Sanksi disebut <strong>aktif</strong> selama sudah diterbitkan, belum dicabut, dan masa berlakunya
            belum lewat. Sanksi aktif juga muncul sebagai kartu di Beranda.
        </p>
        <x-panduan.catatan tipe="info">
            Sanksi aktif memengaruhi <strong>tingkat usulan berikutnya</strong>: sistem menyarankan satu tingkat
            di atas sanksi aktif tertinggi Anda. Urutannya Teguran 1 → Teguran 2 → Teguran 3 → SP 1 → SP 2 → SP 3.
            Setelah SP 3 tidak naik lagi. Bila tidak ada sanksi aktif, saran dimulai dari Teguran 1.
        </x-panduan.catatan>

        {{-- SS: layar sanksi saya --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <x-panduan.peran :peran="['Kepala Unit', 'Pengawas (SPI)']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Menu <strong>Operasional → Disiplin</strong> hanya muncul bila Anda <strong>punya bawahan</strong>,
            atau memegang <strong>jabatan pengawas</strong>. Direktur tidak memakai jalur ini — lihat bagian penerbitan.
        </p>
        <x-panduan.langkah>
            <li>Cari dan pilih <strong>karyawan</strong> yang diusulkan.</li>
            <li>Pilih <strong>tingkat</strong> sanksi. Sistem sudah menyarankan satu tingkat, tetapi boleh Anda ubah.</li>
            <li>Isi <strong>tanggal kejadian</strong>.</li>
            <li>Tulis <strong>uraian</strong> pelanggaran sejelas mungkin — teks ini akan tercetak di surat.</li>
            <li>Kirim. Muncul pesan <span class="kbd">Usulan sanksi terkirim.</span></li>
        </x-panduan.langkah>

        <p class="text-[14px] leading-relaxed mt-4">Penolakan yang mungkin muncul:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-1">
            <li><span class="kbd">Karyawan di luar jangkauan usulan Anda.</span> — orang itu bukan bawahan Anda, sedangkan jabatan Anda bukan jabatan pengawas.</li>
            <li><span class="kbd">Belum ada pemegang peran HRD/Direktur, usulan tak punya penyetuju. Hubungi Admin Sistem.</span> — lihat catatan di bawah.</li>
        </ul>

        {{-- SS: form usul sanksi --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <x-panduan.peran :peran="['Pengawas (SPI)']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Sebagian jabatan — misalnya <strong>SPI</strong> — bertugas mengawasi seluruh rumah sakit, bukan
            memimpin anak buah. Jabatan seperti ini diberi tanda <strong>pengawas</strong> sehingga boleh
            mengusulkan sanksi untuk <strong>karyawan mana pun</strong>, walau unitnya tidak punya anggota.
        </p>
        <p class="text-[14px] leading-relaxed mt-3">Ada dua rute, dipilih saat jabatan itu diatur:</p>
        <div class="grid-wrap mt-2">
            <table class="table">
                <thead><tr><th>Rute</th><th>Artinya</th></tr></thead>
                <tbody>
                    <tr><td><strong>Pengawas — usulan langsung ke HRD</strong></td><td>Usulan langsung ke HRD lalu Direktur, <strong>melewati</strong> koordinator dan kepala bidang unit karyawan yang diusulkan. Pola untuk SPI.</td></tr>
                    <tr><td><strong>Pengawas — usulan lewat atasan karyawan terkait</strong></td><td>Usulan masuk dari <strong>garis komando karyawan yang diusulkan</strong>, naik sampai kepala bidang, baru ke HRD dan Direktur. Pola untuk supervisor.</td></tr>
                </tbody>
            </table>
        </div>

        <x-panduan.catatan tipe="info">
            Tanda pengawas menempel pada <strong>jabatan</strong>, bukan pada orangnya. Jadi ketika pemegang
            jabatan berganti, kewenangannya ikut berpindah sendiri — tidak perlu diatur ulang.
            Pengaturannya ada di SDM → Organisasi, dan hanya tersedia untuk jabatan pimpinan.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Pemegang jabatan pengawas <strong>merekomendasikan, bukan memutuskan</strong>: mereka tidak
            mendapat wewenang menyetujui sanksi maupun cuti.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">
            Rantai disusun otomatis, dan <strong>selalu berakhir di Direktur</strong>.
        </p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-3">
            <li><strong>Pengusul di bawah kepala bidang</strong> — naik lewat atasannya sampai kepala bidang, lalu HRD, lalu Direktur.</li>
            <li><strong>Pengusul setingkat kepala bidang ke atas</strong> — langsung HRD, lalu Direktur.</li>
            <li><strong>Pengusul memegang role HRD</strong> — langsung ke Direktur.</li>
            <li><strong>Direktur</strong> — sanksinya terbit langsung tanpa penyetuju lain.</li>
            <li><strong>Jabatan pengawas</strong> — mengikuti rute yang dipilih, seperti dijelaskan di atas.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Bila pengusul kebetulan juga atasan orang yang diusulkan, dia <strong>dilewati</strong> dari rantai —
            tidak boleh menyetujui usulannya sendiri.
        </p>
        <p class="text-[14px] leading-relaxed mt-3">
            Menyetujui dilakukan di menu <strong>Operasional → Persetujuan Sanksi</strong>, hanya saat giliran Anda.
            Statusnya berjalan: <strong>Diajukan</strong> → <strong>Diproses</strong> → <strong>Diterbitkan</strong>,
            atau berhenti sebagai <strong>Ditolak</strong>.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <p class="text-[14px] leading-relaxed">
            Sanksi yang sudah diterbitkan bisa dicetak sebagai <strong>surat dua halaman</strong>.
            Suratnya memuat kode QR untuk tiap pihak yang menandatangani — penerbit, pengusul, dan kepala bidang.
        </p>
        <p class="text-[14px] leading-relaxed mt-3">
            Memindai QR membuka halaman verifikasi yang menampilkan data asli dari sistem. Halaman itu bisa
            dibuka tanpa login, tetapi tautannya bertanda tangan digital sehingga QR palsu tidak akan terbuka.
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
