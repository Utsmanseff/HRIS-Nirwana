<x-layouts.panduan title="Daftar Isi">
    {{-- Sampul buku: logo di tengah, judul, lalu tombol turun ke daftar isi. --}}
    <section class="text-center py-10">
        <img src="{{ asset('img/icon.png') }}" alt="Logo RSU Nirwana" width="112" height="112"
             class="mx-auto w-28 h-28 rounded-2xl shadow-lg">
        <h1 class="text-[26px] font-extrabold tracking-tight mt-5">Panduan Aplikasi</h1>
        <p class="text-[15px] font-semibold mt-1" style="color:var(--brand-600)">NirwanaHRIS</p>
        <p class="text-[13px] mt-2" style="color:var(--text-muted)">
            Buku pemakaian sistem kepegawaian &amp; presensi<br>RSU Nirwana
        </p>
        <a href="#daftar-isi" class="btn btn-primary btn-sm mt-6">Buka Daftar Isi ↓</a>
    </section>

    <div class="divider my-2"></div>

    <h2 id="daftar-isi" class="text-xl font-extrabold tracking-tight mt-6">Daftar Isi</h2>
    <p class="text-[13.5px] mt-1 mb-5" style="color:var(--text-muted)">
        Klik judul bab untuk langsung membukanya. Setiap bab diberi tanda peran — baca yang sesuai tugas Anda.
        Bab bertanda <span class="badge badge-success">Semua</span> berlaku untuk seluruh karyawan.
    </p>

    @foreach ($grup as $namaGrup => $daftar)
        <h3 class="text-[13px] font-bold uppercase tracking-wider mt-6 mb-2" style="color:var(--text-muted)">{{ $namaGrup }}</h3>
        <div class="space-y-2">
            @foreach ($daftar as $bab)
                <a href="{{ route('panduan.bab', $bab['slug']) }}" class="card card-pad flex gap-3 items-start hover:shadow-md transition">
                    <span class="shrink-0 mt-0.5" style="color:var(--brand-600)"><x-icon :name="$bab['ikon']" :size="20" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-bold text-[14.5px]">{{ $bab['judul'] }}</span>
                        <span class="block text-[12.5px] mt-0.5" style="color:var(--text-muted)">{{ $bab['ringkas'] }}</span>
                        <span class="mt-1.5 block"><x-panduan.peran :peran="$bab['peran']" /></span>
                    </span>
                </a>
            @endforeach
        </div>
    @endforeach
</x-layouts.panduan>
