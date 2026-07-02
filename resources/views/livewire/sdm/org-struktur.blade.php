<div class="space-y-4 rise">
    <div class="flex items-center justify-between">
        <div><h1 class="text-lg font-extrabold tracking-tight">Struktur Organisasi</h1>
            <p class="text-sm text-neutral-500 max-w-xl">Pohon unit organisasi (Bidang → Divisi → Unit). Terpisah dari jabatan.</p></div>
    </div>

    <div class="card card-pad">
        <div class="space-y-0.5">
            @forelse ($akar as $unit)
                @include('livewire.sdm.partials.org-node', ['unit' => $unit])
            @empty
                <p class="text-sm text-neutral-400 py-4 text-center">Belum ada unit organisasi.</p>
            @endforelse
        </div>
    </div>
</div>
