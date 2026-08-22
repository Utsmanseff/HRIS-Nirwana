@php
    $bagian = [
        ['id' => 'siapa', 'judul' => 'Siapa Melihat Apa'],
        ['id' => 'filter', 'judul' => 'Menyaring Data'],
        ['id' => 'kolom', 'judul' => 'Arti Tiap Kolom'],
        ['id' => 'foto', 'judul' => 'Foto Bukti Absen'],
        ['id' => 'ekspor', 'judul' => 'Mengunduh Laporan'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Menu <strong>Operasional → Laporan Absensi</strong> menampilkan rekap kehadiran dalam rentang tanggal
        yang Anda pilih, lengkap dengan foto bukti, dan bisa diunduh sebagai PDF atau Excel.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <div class="grid-wrap">
            <table class="table">
                <thead><tr><th>Peran</th><th>Cakupan data</th></tr></thead>
                <tbody>
                    <tr><td>HRD, Staff HR, Admin Sistem</td><td>Seluruh rumah sakit, semua unit.</td></tr>
                    <tr><td>Kepala unit (koordinator, kabag, kabid)</td><td>Hanya unit yang dipimpin <strong>beserta seluruh unit di bawahnya</strong>.</td></tr>
                    <tr><td>Direktur</td><td>Bisa membaca rekap sebagai informasi pimpinan.</td></tr>
                </tbody>
            </table>
        </div>

        <x-panduan.catatan tipe="info">
            Batasan ini berlaku <strong>di layar maupun di berkas hasil unduhan</strong>. Kepala unit yang
            mencoba memilih unit di luar wilayahnya akan otomatis dikembalikan ke unitnya sendiri —
            bukan ditolak dengan pesan galat, melainkan diam-diam dibatasi. Jadi bila daftar unit terasa pendek,
            itu memang lingkup Anda.
        </x-panduan.catatan>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed">
            <li><strong>Dari</strong> dan <strong>Sampai</strong> — rentang tanggal.</li>
            <li><strong>Cari</strong> — nama atau NIP karyawan.</li>
            <li><strong>Unit</strong> — batasi ke satu unit (berikut turunannya).</li>
            <li><strong>Status</strong> — hanya tampilkan baris tertentu: normal, telat, pulang cepat, atau anomali.</li>
        </ul>
        <p class="text-[14px] leading-relaxed mt-3">
            Semua filter langsung berlaku tanpa tombol cari, dan ikut terbawa saat Anda mengunduh —
            berkas yang keluar berisi data yang sama persis dengan yang sedang tampil di layar.
        </p>
        <p class="text-[14px] leading-relaxed mt-2">
            Di bagian atas ada ringkasan jumlah: total kehadiran, telat, pulang cepat, dan anomali.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <div class="grid-wrap">
            <table class="table">
                <thead><tr><th>Kolom</th><th>Isinya</th></tr></thead>
                <tbody>
                    <tr><td><strong>Tanggal</strong></td><td>Tanggal kerja.</td></tr>
                    <tr><td><strong>Karyawan</strong></td><td>Nama dan NIP.</td></tr>
                    <tr><td><strong>Shift</strong></td><td>Nama shift yang tersimpan saat absen. Kosong berarti hari itu tidak ada jadwal.</td></tr>
                    <tr><td><strong>Masuk</strong> / <strong>Pulang</strong></td><td>Jam absen. Pulang yang kosong berarti sesi belum ditutup.</td></tr>
                    <tr><td><strong>Jam Kerja</strong></td><td>Lama bekerja, ditulis seperti <span class="kbd">7j 30m</span>.</td></tr>
                    <tr><td><strong>Status</strong></td><td>Normal / Tercatat / Telat / Pulang cepat / Anomali, beserta lamanya.</td></tr>
                    <tr><td><strong>Keterangan</strong></td><td>Catatan penugasan pengganti, misalnya “Pengganti cuti — <em>nama</em>”.</td></tr>
                    <tr><td><strong>Foto</strong></td><td>Foto absen masuk dan pulang.</td></tr>
                </tbody>
            </table>
        </div>

        <p class="text-[14px] leading-relaxed mt-4">
            Arti status dan rumus pulang cepat dijelaskan lengkap di bab
            <a href="{{ route('panduan.bab', 'absensi') }}" class="hover:underline" style="color:var(--brand-600)">Absen Masuk &amp; Pulang</a>.
        </p>

        <x-panduan.catatan tipe="awas">
            <strong>Anomali</strong> layak ditindaklanjuti, bukan diabaikan. Sesi di bawah 5 menit hampir selalu
            berarti tombol pulang tertekan tak sengaja; sesi di atas 16 jam berarti seseorang lupa absen pulang.
            Keduanya membuat jam kerja bulan itu tidak mencerminkan kenyataan.
        </x-panduan.catatan>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">
            Kolom Foto menampilkan gambar kecil untuk sesi masuk dan pulang. Tekan salah satunya untuk melihat
            versi besar. Foto hanya bisa dibuka oleh orang yang memang berhak atas data absensi itu —
            aturannya sama persis dengan aturan siapa boleh melihat laporan.
        </p>

        <x-panduan.catatan tipe="info">
            <strong>Foto sengaja tidak ikut ke PDF maupun Excel.</strong> Berkas unduhan hanya berisi angka dan teks.
            Foto hanya tersedia di layar, dan hanya bagi yang berwenang — supaya bukti kehadiran tidak ikut
            tersebar begitu berkas laporan diteruskan ke orang lain.
        </x-panduan.catatan>

        <p class="text-[13.5px] leading-relaxed mt-3" style="color:var(--text-muted)">
            Gambar dimuat bertahap, beberapa sekaligus, bukan sekali serentak. Kalau ada thumbnail yang lambat
            atau sempat gagal, aplikasi mencobanya lagi sendiri — tunggu sebentar sebelum memuat ulang halaman.
        </p>

        {{-- SS: laporan absensi dengan kolom foto --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <p class="text-[14px] leading-relaxed">Tersedia dua format, masing-masing dua susunan:</p>
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed mt-2">
            <li><strong>Excel (.xlsx)</strong> — untuk diolah lagi. Pilihan <em>Per Unit</em> memecah datanya menjadi satu lembar per unit.</li>
            <li><strong>PDF</strong> — untuk dicetak atau diarsipkan, ukuran A4 melintang. Pilihan <em>Per Unit</em> memulai halaman baru tiap unit.</li>
        </ul>

        <x-panduan.catatan tipe="info">
            Di Excel, lama telat, pulang cepat, dan jam kerja ditulis sebagai <strong>angka menit</strong>,
            bukan “2j 15m”. Itu disengaja supaya kolomnya bisa dijumlah dan dirata-rata langsung.
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
