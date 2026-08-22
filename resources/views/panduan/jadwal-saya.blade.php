@php
    $bagian = [
        ['id' => 'membaca', 'judul' => 'Membaca Jadwal Anda'],
        ['id' => 'dinas-ganda', 'judul' => 'Dinas Ganda: Dua Shift dalam Sehari'],
        ['id' => 'pengganti', 'judul' => 'Tanda Shift Pengganti'],
        ['id' => 'salah', 'judul' => 'Kalau Jadwal Anda Salah'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Halaman <strong>Jadwal Saya</strong> (menu <strong>Operasional → Jadwal Saya</strong>) menampilkan
        shift Anda satu bulan penuh. Jadwal ini disusun oleh kepala unit Anda; di halaman ini Anda hanya melihat,
        tidak mengubah.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <ul class="list-disc ms-5 space-y-1.5 text-[14px] leading-relaxed">
            <li>Nama bulan tampil di bagian atas. Tombol panah kiri/kanan di sisinya memindah bulan — mundur maupun maju, tanpa batas.</li>
            <li>Setiap baris adalah satu tanggal berisi shift Anda hari itu: nama shift beserta jam mulai dan jam selesainya.</li>
            <li>Tanggal yang tidak muncul berarti Anda memang tidak dijadwalkan hari itu.</li>
        </ul>

        <x-panduan.catatan tipe="info">
            Jam pada jadwal inilah yang dipakai menilai keterlambatan dan jam kerja Anda saat absen.
            Kalau hari itu Anda tidak punya jadwal tetapi tetap absen, absennya tetap tersimpan —
            hanya saja statusnya <strong>Tercatat</strong>, tanpa penilaian telat atau pulang cepat.
        </x-panduan.catatan>

        {{-- SS: jadwal saya di HP --}}
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">
            Satu tanggal boleh memuat <strong>lebih dari satu shift</strong>. Kalau begitu, kedua shift tampil
            berurutan pada tanggal yang sama, diurutkan dari jam mulai paling awal.
        </p>
        <p class="text-[14px] leading-relaxed mt-3">
            Anda harus <strong>absen masuk dan absen pulang untuk masing-masing shift</strong>. Saat Anda menekan
            Absen Masuk, aplikasi memilih shift yang jam mulainya paling dekat dengan waktu Anda absen
            <em>dan</em> belum dipakai hari itu — jadi shift kedua tidak akan tertukar dengan yang pertama.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <p class="text-[14px] leading-relaxed">
            Sebagian shift diberi keterangan kecil berwarna di bawah nama shift. Itu artinya Anda ditugaskan
            <strong>menggantikan orang lain</strong>:
        </p>
        <div class="grid-wrap mt-3">
            <table class="table">
                <thead><tr><th>Keterangan</th><th>Artinya</th></tr></thead>
                <tbody>
                    <tr><td><strong>Pengganti cuti — <em>nama</em></strong></td><td>Anda mengisi shift milik rekan yang sedang cuti.</td></tr>
                    <tr><td><strong>Mengisi jadwal kosong — <em>nama</em></strong></td><td>Anda mengisi shift yang kosong karena pemegangnya sudah tidak aktif.</td></tr>
                </tbody>
            </table>
        </div>
        <p class="text-[14px] leading-relaxed mt-3">
            Kewajiban absennya sama persis dengan dinas biasa. Keterangan ini juga ikut muncul di laporan absensi,
            supaya jelas mengapa Anda masuk di luar pola jadwal Anda yang biasa.
        </p>
        <p class="text-[14px] leading-relaxed mt-2">
            Detail penugasan pengganti dibahas di bab
            <a href="{{ route('panduan.bab', 'pengganti') }}" class="hover:underline" style="color:var(--brand-600)">Pengganti Jadwal</a>.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <p class="text-[14px] leading-relaxed">
            Jadwal tidak bisa Anda perbaiki sendiri. Laporkan ke <strong>kepala unit Anda</strong>
            (koordinator, atau kepala bidang bila unit Anda tidak punya koordinator) — merekalah yang menyusun
            dan mengubah jadwal di menu <strong>Jadwal Shift</strong>.
        </p>
        <x-panduan.catatan tipe="awas">
            Laporkan <strong>sebelum</strong> tanggalnya lewat. Jadwal yang sudah terlanjur dipakai sebagai patokan
            absen akan ikut memengaruhi hitungan telat dan jam kerja Anda di laporan bulan itu.
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
