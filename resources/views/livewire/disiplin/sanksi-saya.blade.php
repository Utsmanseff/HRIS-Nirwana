<div class="space-y-6 rise">
    <div>
        <h1 class="text-xl font-extrabold tracking-tight">Sanksi Saya</h1>
        <p class="text-sm text-neutral-500">Catatan sanksi disiplin atas nama Anda.</p>
    </div>

    @if ($aktif->isNotEmpty())
        <div class="card card-pad" style="border-color:var(--warning-200)">
            <div class="text-[13px] text-warning-700">
                <b>{{ $aktif->count() }} sanksi aktif.</b> Hindari pelanggaran berulang selama masa berlaku agar tidak naik ke tingkat berikutnya.
            </div>
        </div>
    @endif

    <div class="space-y-3">
        <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">Aktif</div>
        @forelse ($aktif as $s)
            @include('livewire.disiplin.partials.kartu-sanksi', ['s' => $s])
        @empty
            <p class="text-sm text-neutral-400">Tidak ada sanksi aktif.</p>
        @endforelse
    </div>

    <div class="space-y-3">
        <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider">Riwayat</div>
        @forelse ($riwayat as $s)
            @include('livewire.disiplin.partials.kartu-sanksi', ['s' => $s])
        @empty
            <p class="text-sm text-neutral-400">Belum ada riwayat.</p>
        @endforelse
    </div>
</div>
