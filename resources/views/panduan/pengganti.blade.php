@php
    $bagian = [
        ['id' => 'kapan', 'judul' => 'Kapan Lowongan Muncul'],
        ['id' => 'menunjuk', 'judul' => 'Menunjuk Pengganti'],
        ['id' => 'alih', 'judul' => 'Mengalihkan di Tengah Jalan (Estafet)'],
        ['id' => 'ajukan-diri', 'judul' => 'Mengajukan Diri sebagai Pengganti'],
        ['id' => 'tutup', 'judul' => 'Menutup Lowongan'],
    ];
@endphp
<x-layouts.panduan :title="$meta['judul']" :bab="$meta">
    <x-panduan.isi-bab :bagian="$bagian" />

    <p class="text-[14px] leading-relaxed">
        Menu <strong>Operasional → Pengganti Jadwal</strong> menutup shift yang ditinggalkan: entah karena
        pemegangnya sedang cuti, atau karena orangnya sudah tidak aktif dan slot jadwalnya kosong.
        Penunjukan di sini otomatis membuat baris jadwal untuk si pengganti — tidak perlu diketik ulang
        di grid jadwal.
    </p>

    <x-panduan.bagian :id="$bagian[0]['id']" :judul="$bagian[0]['judul']">
        <p class="text-[14px] leading-relaxed">Ada dua sumber lowongan:</p>
        <div class="grid-wrap mt-3">
            <table class="table">
                <thead><tr><th>Jenis</th><th>Muncul karena</th></tr></thead>
                <tbody>
                    <tr><td><strong>Pengganti cuti</strong></td><td>Seseorang mengajukan cuti, dan shift-nya perlu ditutup selama dia pergi.</td></tr>
                    <tr><td><strong>Mengisi jadwal kosong</strong></td><td>Karyawan berstatus nonaktif tetapi slot jadwalnya masih ada di pola unit.</td></tr>
                </tbody>
            </table>
        </div>

        <x-panduan.catatan tipe="awas">
            Fitur ini hanya berjalan untuk unit yang <strong>diaktifkan</strong> — di menu SDM → Organisasi ada
            penanda <span class="kbd">Pengganti: on</span> / <span class="kbd">Pengganti: off</span> per unit.
            Kalau unit Anda masih <em>off</em>, kolom pilih pengganti tidak akan muncul saat mengajukan cuti,
            dan lowongannya tidak akan terbit. Minta HRD atau Admin Sistem menyalakannya.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Baris pengganti punya dua status: <strong>Aktif</strong> (sudah berlaku) dan
            <strong>Menunggu Acc</strong> (usulan yang belum disetujui koordinator).
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[1]['id']" :judul="$bagian[1]['judul']">
        <p class="text-[14px] leading-relaxed">
            Pengganti bisa diusulkan pemohon saat mengajukan cuti, lalu ditetapkan oleh koordinator unit.
            Penunjukan berlaku untuk <strong>seluruh rentang</strong> kasus itu.
        </p>

        <x-panduan.catatan tipe="info">
            Sistem menolak pengganti yang <strong>shift-nya sendiri bentrok</strong> dengan shift yang harus ditutup.
            Pesannya menyebut tanggal dan shift penyebabnya, supaya jelas hari mana yang bermasalah —
            Anda tinggal menunjuk orang lain untuk kasus itu.
        </x-panduan.catatan>

        <p class="text-[14px] leading-relaxed mt-4">
            Untuk lowongan karena cuti, baris jadwal pengganti <strong>baru benar-benar terbentuk setelah
            cuti itu disetujui</strong>. Selama pengajuan cutinya masih berjalan, penunjukan tersimpan sebagai
            rencana. Untuk lowongan karena karyawan nonaktif, jadwalnya langsung terbentuk.
        </p>

        <x-panduan.gambar src="pengganti.png" caption="Layar Pengganti Jadwal" />
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[2]['id']" :judul="$bagian[2]['judul']">
        <x-panduan.peran :peran="['Kepala Unit']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Kalau pengganti pertama berhalangan di tengah masa cuti, koordinator bisa
            <strong>mengalihkan</strong> sisanya ke orang lain.
        </p>
        <x-panduan.langkah>
            <li>Buka kasus yang bersangkutan, pilih alihkan.</li>
            <li>Tentukan <strong>tanggal mulai</strong> peralihan.</li>
            <li>Pilih pengganti baru.</li>
        </x-panduan.langkah>
        <p class="text-[14px] leading-relaxed mt-3">
            Pengganti lama tetap memegang hari-hari <strong>sebelum</strong> tanggal itu; pengganti baru memegang
            sisanya sampai ujung kasus. Muncul pesan <span class="kbd">Cakupan pengganti dialihkan.</span>
        </p>
        <p class="text-[14px] leading-relaxed mt-2">
            Yang boleh mengalihkan hanya <strong>koordinator unit pemohon</strong>. Yang lain akan mendapat pesan
            <span class="kbd">Hanya koordinator unit pemohon yang boleh mengalihkan.</span>
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[3]['id']" :judul="$bagian[3]['judul']">
        <x-panduan.peran :peran="['Karyawan']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Rekan satu unit dengan orang yang cuti boleh <strong>menawarkan diri</strong> menutup sisa hari,
            tanpa menunggu ditunjuk.
        </p>
        <x-panduan.langkah>
            <li>Buka kasusnya, pilih ajukan diri.</li>
            <li>Tentukan tanggal mulai Anda mengambil alih.</li>
            <li>Kirim. Muncul pesan <span class="kbd">Usulan terkirim, menunggu acc koordinator.</span></li>
        </x-panduan.langkah>
        <p class="text-[14px] leading-relaxed mt-3">
            Usulan berstatus <strong>Menunggu Acc</strong> dan belum mengubah jadwal siapa pun. Koordinator unit
            kemudian menyetujui (<span class="kbd">Usulan disetujui.</span>) atau menolak
            (<span class="kbd">Usulan ditolak.</span>). Kalau ditolak, cakupan pengganti tidak berubah sama sekali.
        </p>
    </x-panduan.bagian>

    <x-panduan.bagian :id="$bagian[4]['id']" :judul="$bagian[4]['judul']">
        <x-panduan.peran :peran="['Kepala Unit']" />
        <p class="text-[14px] leading-relaxed mt-3">
            Lowongan karena karyawan nonaktif bersifat terbuka — tidak punya tanggal berakhir. Tutup lowongan
            itu bila slotnya sudah tidak perlu ditutup lagi, misalnya karena sudah ada karyawan pengganti tetap
            atau polanya sudah dirapikan.
        </p>
        <p class="text-[14px] leading-relaxed mt-2">
            Yang boleh menutup hanya <strong>koordinator unit</strong>; yang lain mendapat pesan
            <span class="kbd">Hanya koordinator unit yang boleh menutup lowongan.</span>
        </p>
        <x-panduan.catatan tipe="info">
            Lowongan juga tertutup <strong>otomatis</strong> ketika Anda mengeluarkan karyawan nonaktif itu
            dari semua pola jadwal unit — karena slotnya sudah diambil orang lain, tidak ada lagi yang perlu ditutup.
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
