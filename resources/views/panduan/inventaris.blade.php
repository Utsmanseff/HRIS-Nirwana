@php
    $bagian = [
        ['id' => 'siapa', 'judul' => 'Siapa yang Mengelola'],
        ['id' => 'kategori', 'judul' => 'Kategori Aset'],
        ['id' => 'tambah', 'judul' => 'Menambah & Mengubah Aset'],
        ['id' => 'detail', 'judul' => 'Mutasi, Jadwal Pemeliharaan, dan Lampiran'],
        ['id' => 'laporan', 'judul' => 'Laporan Inventaris'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Modul <strong>Inventaris</strong> mendata aset milik rumah sakit: identitas, lokasi, kondisi,
        jadwal pemeliharaan, dan berkas pendukungnya.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <x-panduan.peran :peran="['Tim Teknis']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Menu ini hanya muncul untuk <strong>anggota tim teknis</strong> — pemegang role
            <strong>IT</strong>, <strong>Teknisi</strong> (Sarana), atau <strong>ATEM</strong>,
            serta Admin Sistem. Karyawan lain tidak melihatnya.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">
            Sebelum mendata aset, siapkan kategorinya di <strong>Inventaris → Kategori</strong>.
            Tiap kategori dimiliki satu <strong>tim</strong> — itulah yang menentukan tim mana melihat
            dan mengurus aset di dalamnya. Kategori yang tak dipakai lagi cukup dinonaktifkan,
            supaya aset lama tetap punya kategori.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <x-panduan.langkah>
            <li>Buka <strong>Inventaris</strong>, tekan tambah aset.</li>
            <li>Isi <strong>kode</strong> aset — <strong>wajib unik</strong> di seluruh sistem, maksimal 50 karakter. Pakai penomoran yang konsisten sejak awal.</li>
            <li>Isi <strong>nama</strong> aset (maksimal 150 huruf) dan pilih <strong>kategori</strong>.</li>
            <li>Lengkapi <strong>merk</strong>, <strong>model</strong>, dan <strong>nomor seri</strong> bila ada.</li>
            <li>Isi <strong>tanggal pengadaan</strong> dan <strong>nilai perolehan</strong>.</li>
            <li>Tentukan <strong>unit</strong> tempat aset berada dan <strong>penanggung jawab</strong>-nya.</li>
            <li>Pilih <strong>status</strong>, lalu simpan.</li>
        </x-panduan.langkah>

        <div class="grid-wrap mt-4">
            <table class="table">
                <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
                <tbody>
                    <tr><td><strong>Baik</strong></td><td>Layak dan sedang dipakai.</td></tr>
                    <tr><td><strong>Rusak</strong></td><td>Tidak bisa dipakai, belum ditangani.</td></tr>
                    <tr><td><strong>Dalam Perbaikan</strong></td><td>Sedang ditangani. Status ini juga bisa terpasang otomatis dari tiket perbaikan yang tertaut ke aset.</td></tr>
                    <tr><td><strong>Afkir</strong></td><td>Sudah dihapus dari pemakaian. Aset afkir tidak lagi memunculkan pengingat pemeliharaan.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- SS: daftar aset --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">Halaman detail aset punya beberapa tab.</p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Mutasi</h3>
        <p class="text-[14px] leading-relaxed">
            Memindahkan aset ke unit lain, disertai catatan. Perpindahannya tersimpan sebagai riwayat,
            jadi terlihat aset itu pernah ada di mana saja.
        </p>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Jadwal pemeliharaan</h3>
        <x-panduan.langkah>
            <li>Beri <strong>nama</strong> pekerjaannya, misalnya “Kalibrasi” atau “Servis rutin”.</li>
            <li>Isi <strong>interval</strong> dalam hari — misalnya 180 untuk tiap enam bulan.</li>
            <li>Simpan. Setelah pekerjaan dilakukan, tandai selesai; jadwal berikutnya dihitung dari situ.</li>
        </x-panduan.langkah>
        <x-panduan.catatan tipe="info">
            Pengingat muncul otomatis mulai <strong>14 hari sebelum</strong> jatuh tempo, dan tetap tampil
            bila sudah terlewat. Jumlahnya terlihat di kartu Inventaris pada Beranda —
            dihitung sendiri dari jadwal, jadi tidak ada yang perlu ditandai manual sebagai “sudah diingatkan”.
        </x-panduan.catatan>

        <h3 class="text-[15px] font-bold mt-5 mb-2">Lampiran</h3>
        <p class="text-[14px] leading-relaxed">
            Unggah berkas pendukung seperti faktur, garansi, atau sertifikat kalibrasi, lengkap dengan
            tanggal dan masa berlakunya.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <p class="text-[14px] leading-relaxed">
            Menu <strong>Inventaris → Laporan</strong> menyediakan dua rekap: <strong>daftar aset</strong>
            dan <strong>pemeliharaan</strong>, keduanya bisa disaring dan diunduh.
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
