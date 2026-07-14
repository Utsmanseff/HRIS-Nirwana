<div class="space-y-4 rise">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Notifikasi</h1>
            <p class="text-neutral-500 text-sm mt-1">
                @if ($jumlahBelumDibaca > 0)
                    {{ $jumlahBelumDibaca }} belum dibaca
                @else
                    Semua sudah dibaca.
                @endif
            </p>
        </div>
        @if ($jumlahBelumDibaca > 0)
            <button wire:click="tandaiSemuaDibaca" class="btn btn-secondary btn-sm">Tandai semua dibaca</button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="divide-y divide-neutral-100">
            @forelse ($daftar as $n)
                @php
                    $type = class_basename($n->type ?? '');
                    $ic = str_contains($type, 'Cuti') ? 'calendar'
                        : (str_contains($type, 'Sanksi') ? 'gavel'
                        : (str_contains($type, 'Kontrak') ? 'doc'
                        : (str_contains($type, 'Pemeliharaan') ? 'box'
                        : (str_contains($type, 'Tiket') ? 'ticket' : 'bell'))));
                    $belum = $n->read_at === null;
                @endphp
                <a href="{{ $n->data['url'] ?? '#' }}" wire:click="tandaiDibaca('{{ $n->id }}')"
                   class="flex gap-3 px-4 py-3.5 hover:bg-neutral-50 transition {{ $belum ? 'bg-brand-50/40' : '' }}">
                    <span class="shrink-0 w-10 h-10 rounded-xl grid place-items-center {{ $belum ? 'bg-brand-50 text-brand-600' : 'bg-neutral-100 text-neutral-400' }}">
                        <x-icon :name="$ic" :size="20" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm {{ $belum ? 'font-semibold text-neutral-800' : 'text-neutral-600' }} leading-snug">
                            {{ $n->data['pesan'] ?? '—' }}
                        </div>
                        <div class="text-[11px] text-neutral-400 mt-1">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                    @if ($belum)
                        <span class="mt-1.5 w-2 h-2 rounded-full bg-brand-500 shrink-0"></span>
                    @endif
                </a>
            @empty
                <div class="px-4 py-16 text-center">
                    <span class="mx-auto w-14 h-14 rounded-2xl bg-neutral-100 text-neutral-300 grid place-items-center mb-3">
                        <x-icon name="bell" :size="26" />
                    </span>
                    <div class="text-sm font-semibold text-neutral-500">Belum ada notifikasi</div>
                    <p class="text-xs text-neutral-400 mt-1">Pemberitahuan cuti, absensi, tiket & lainnya muncul di sini.</p>
                </div>
            @endforelse
        </div>
        {{ $daftar->links('livewire.sdm.partials.pager') }}
    </div>
</div>
