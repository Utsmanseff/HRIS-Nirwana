<x-layouts.app title="Design System" active="" :breadcrumb="['Sistem', 'Styleguide']">
    <div class="space-y-6 rise">

        <div>
            <h2 class="text-2xl font-extrabold tracking-tight">Nirwana HRIS — Design System</h2>
            <p class="text-neutral-500 mt-1">Token & komponen hasil port dari <span class="font-mono text-sm">docs/mockups</span> ke Tailwind v4 + Blade. Coba toggle tema (ikon di topbar).</p>
        </div>

        {{-- Buttons --}}
        <div class="card card-pad">
            <div class="card-title mb-4">Buttons</div>
            <div class="flex flex-wrap gap-3 items-center">
                <button class="btn btn-primary">Primer</button>
                <button class="btn btn-secondary">Sekunder</button>
                <button class="btn btn-ghost">Ghost</button>
                <button class="btn btn-danger">Danger</button>
                <button class="btn btn-primary btn-sm">Small</button>
                <button class="btn btn-primary btn-lg">Large</button>
                <button class="btn btn-secondary btn-icon" aria-label="cog"><x-icon name="cog" :size="18" /></button>
            </div>
        </div>

        {{-- Badges --}}
        <div class="card card-pad">
            <div class="card-title mb-4">Badges</div>
            <div class="flex flex-wrap gap-2.5 items-center">
                <span class="badge badge-neutral"><span class="dot"></span>Neutral</span>
                <span class="badge badge-brand"><span class="dot"></span>Brand</span>
                <span class="badge badge-success"><span class="dot"></span>Aktif</span>
                <span class="badge badge-warning"><span class="dot"></span>Akan berakhir</span>
                <span class="badge badge-danger"><span class="dot"></span>Terlewat</span>
                <span class="badge badge-info"><span class="dot"></span>Info</span>
            </div>
        </div>

        {{-- Colors --}}
        <div class="card card-pad">
            <div class="card-title mb-4">Brand & Neutral</div>
            <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
                @foreach (['50','100','200','300','400','500','600','700','800','900'] as $s)
                    <div class="space-y-1">
                        <div class="h-12 rounded-md border border-neutral-200" style="background:var(--brand-{{ $s }})"></div>
                        <div class="text-[10px] text-center text-neutral-500 font-mono">{{ $s }}</div>
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-5 sm:grid-cols-10 gap-2 mt-3">
                @foreach (['0','50','100','200','300','400','500','600','700','800','900'] as $s)
                    <div class="space-y-1">
                        <div class="h-12 rounded-md border border-neutral-200" style="background:var(--neutral-{{ $s }})"></div>
                        <div class="text-[10px] text-center text-neutral-500 font-mono">{{ $s }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Typography --}}
        <div class="card card-pad">
            <div class="card-title mb-4">Tipografi</div>
            <p class="text-2xl font-extrabold tracking-tight">Plus Jakarta Sans — Heading</p>
            <p class="text-neutral-600 mt-1">Body teks reguler. Padat data, tenang, klinis.</p>
            <p class="font-mono text-sm mt-2 tnum">IBM Plex Mono · NIP 1990.04.21.001 · Rp 4.250.000</p>
        </div>

        {{-- Inputs --}}
        <div class="card card-pad">
            <div class="card-title mb-4">Form</div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">NIP</label>
                    <input class="input font-mono" placeholder="1990.04.21.001">
                    <p class="field-hint">Nomor pegawai (manual, unik).</p>
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <select class="select"><option>Aktif</option><option>Nonaktif</option></select>
                </div>
            </div>
        </div>

        {{-- Table (responsive) --}}
        <div class="card overflow-hidden">
            <div class="card-header"><div class="card-title">Tabel responsif (.rtable)</div></div>
            <div class="px-2 sm:px-0">
                <table class="table rtable">
                    <thead><tr><th>Nama</th><th>NIP</th><th>Unit</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr>
                            <td data-primary><div class="font-semibold">Siti Rahmawati</div></td>
                            <td data-label="NIP"><span class="font-mono text-sm">1990.04.21.001</span></td>
                            <td data-label="Unit">Farmasi</td>
                            <td data-label="Status"><span class="badge badge-success"><span class="dot"></span>Aktif</span></td>
                        </tr>
                        <tr>
                            <td data-primary><div class="font-semibold">Budi Santoso</div></td>
                            <td data-label="NIP"><span class="font-mono text-sm">1988.11.02.014</span></td>
                            <td data-label="Unit">Kasir</td>
                            <td data-label="Status"><span class="badge badge-warning"><span class="dot"></span>PKWT H-12</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.app>
