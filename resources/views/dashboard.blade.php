<x-layouts.app title="Dashboard" active="dashboard">
    <div class="space-y-4 rise">
        <div class="card card-pad">
            <div class="card-title">Selamat datang, {{ auth()->user()->karyawan?->nama_lengkap ?? auth()->user()->name }}</div>
            <p class="text-neutral-500 mt-1">Dashboard Nirwana HRIS. Ringkasan & pengingat kontrak menyusul di Fase 1b.</p>
        </div>
    </div>
</x-layouts.app>
